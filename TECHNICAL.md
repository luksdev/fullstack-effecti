# Documentação Técnica — Teste Effecti

## 1. Estrutura da aplicação

O fluxo de uma requisição segue camadas com responsabilidades estreitas:

```
Controller (fino) → FormRequest (validação) → Service/Domain → Model → Resource (saída)
```

O núcleo de negócio — o cálculo de preço — vive em `app/Domain/Pricing`,
isolado do framework (sem dependência de Eloquent além de receber o `Contract`
para ler os itens). O restante (CRUDs) usa controllers finos sobre os models.

Recorte das pastas relevantes:

```
app/
  Domain/Pricing/
    Contracts/PricingRule.php          # interface das regras
    DTOs/PricingResult.php             # subtotal + adjustments + total()
    DTOs/Adjustment.php                # label + amount (centavos)
    Rules/QuantityDiscountRule.php
    Rules/ProgressiveDiscountRule.php
    ContractPricingService.php         # encadeia as regras
  Enums/CustomerStatus.php  ContractStatus.php
  Http/
    Controllers/{Customer,Service,Contract}Controller.php
    Controllers/Api/{Customer,Service,Contract,ContractItem}Controller.php
    Requests/{Customer,Service,Contract}/...Request.php
    Resources/{Customer,Service,Contract,ContractItem}Resource.php
  Models/{Customer,Service,Contract,ContractItem,User}.php
  Policies/ContractPolicy.php
  Rules/CpfCnpj.php                    # validação de documento
  Services/ContractItemService.php     # congela o unit_price na adição
  Support/Money.php                    # centavos → string reais (apresentação)
  Providers/PricingServiceProvider.php # registra as regras (tagged binding)
resources/js/pages/{customers,services,contracts}/  Dashboard.vue  auth/
lang/pt_BR/validation.php              # mensagens do validator em pt-BR
database/seeders/DatabaseSeeder.php
```

## 2. Organização das camadas

- **Controllers** — finos. Recebem o request validado, delegam a persistência ao
  model ou a um serviço e devolvem um `Resource` (API) ou uma página Inertia
  (web). Não contêm regra de negócio.
- **FormRequests** — toda validação de entrada (incluindo a Rule `CpfCnpj`, o
  unique ignorando o próprio registro no update e o bloqueio de item duplicado).
- **Services / Domain** — onde mora a lógica. Dois conjuntos distintos:
  - `app/Domain/Pricing` é o **domínio** de precificação: regras de negócio puras,
    sem framework, testáveis isoladamente. Fica separado de `app/Services` de
    propósito, porque é o coração do problema (regras compostas) e se beneficia
    de não depender de infraestrutura.
  - `app/Services/ContractItemService` é um serviço de **aplicação**: orquestra a
    criação de um item resolvendo o preço-default a partir do serviço. É a regra
    "congelar o preço" reutilizada por web e API.
- **Resources** — serializam a saída e fazem a conversão de centavos para reais
  na borda de apresentação.

A **API e a web compartilham a mesma camada de serviço e os mesmos
FormRequests/Resources**: os controllers são diferentes (respostas Inertia vs.
JSON), mas a lógica é única. O `ContractItemService` e o `ContractPricingService`
são chamados pelos dois lados; a regra de cancelamento é uma Policy aplicada nos
dois.

## 3. Modelagem das entidades

Quatro entidades de domínio:

- **Customer** — `name`, `federal_document` (CPF/CNPJ, único), `email` (único),
  `status` (enum). Soft deletes. `hasMany` contracts.
- **Service** — `name`, `base_price` (centavos). Soft deletes.
- **Contract** — `customer_id`, `start_date`, `end_date` (nullable), `status`
  (enum). Soft deletes. `belongsTo` customer, `hasMany` contractItems.
- **ContractItem** — `contract_id`, `service_id`, `quantity`, `unit_price`
  (centavos). Sem soft deletes. `unique(contract_id, service_id)`.

Decisões de modelagem e o porquê:

- **PK em UUID v7** (`HasUuids`; os ids gerados têm nibble de versão `7`,
  ex.: `019ec41f-0f83-7096-...`). Usado porque não é enumerável (não expõe
  contagem/crescimento de registros nas URLs da API) e, sendo ordenável por
  tempo, mantém boa localidade de índice — diferente de um UUID v4 puro.
- **Dinheiro em centavos inteiros** (`bigInteger`). Evita os erros de
  arredondamento de ponto flutuante; toda a aritmética de preço é feita em
  inteiros e a conversão para reais acontece só na saída.
- **`unit_price` congelado no item.** O preço fica gravado no `ContractItem` no
  momento da adição. Alterar depois o `base_price` do serviço **não** muda
  contratos já existentes — o histórico financeiro do contrato é preservado.
- **FKs com cascade/restrict deliberados.** `contract_items.contract_id` é
  `cascadeOnDelete` (o item é parte/composição do contrato e não existe sem ele);
  `contracts.customer_id` e `contract_items.service_id` são `restrictOnDelete`
  (não se apaga um cliente/serviço que ainda sustenta um contrato/item — preserva
  o histórico).
- **`unique(contract_id, service_id)`.** Um mesmo serviço aparece no máximo uma
  vez por contrato; mudança de quantidade é feita removendo e re-adicionando.
- **Enums na aplicação, status como string no banco.** O status é um enum PHP
  (`CustomerStatus`, `ContractStatus`) com cast no model, mas a coluna é
  `string` — dá flexibilidade para adicionar/renomear casos sem migração de tipo
  nativo do banco.
- **Soft deletes** em Customer, Service e Contract (exclusão reversível,
  preservando referências); ContractItem não usa, pois é removido de fato do
  contrato.

## 4. Implementação das regras de negócio (pricing)

Esta é a parte central.

**Subtotal.** O `ContractPricingService` carrega os itens e soma
`quantity * unit_price` de cada um (tudo em centavos), produzindo o subtotal.

**Padrão de regras (chain of responsibility / strategy):**

- `PricingRule` é uma interface com um único método:
  `apply(Contract $contract, PricingResult $result): PricingResult`.
- `PricingResult` (subtotal + lista de `Adjustment` + método `total()`) e
  `Adjustment` (label + amount em centavos) são **DTOs imutáveis**
  (propriedades `readonly`). Cada regra não muta o resultado: devolve um novo
  `PricingResult` com o ajuste acrescentado.
- O `ContractPricingService` recebe as regras por injeção e as **encadeia**:
  começa com `PricingResult(subtotal, [])` e passa o resultado por cada regra.

**As duas regras implementadas:**

- `QuantityDiscountRule` — se a soma das quantidades dos itens for `>= minQuantity`
  (default **3**), aplica `discountPercentage` (default **5%**) sobre o subtotal.
- `ProgressiveDiscountRule` — por faixa de subtotal, aplicando apenas a **maior
  faixa atingida**: `>= R$ 1.000,00` (100000 centavos) → **10%**; senão
  `>= R$ 500,00` (50000 centavos) → **5%**.

**Como adicionar uma regra nova.** Criar uma classe que implemente `PricingRule`
e adicioná-la à tag no `PricingServiceProvider`:

```php
$this->app->tag([
    QuantityDiscountRule::class,
    ProgressiveDiscountRule::class,
    MinhaNovaRegra::class,   // <- uma linha
], 'pricing.rules');
```

O `ContractPricingService` recebe `$app->tagged('pricing.rules')` e passa a
considerá-la automaticamente. O serviço **não é tocado** — é o princípio
Open/Closed na prática (aberto a extensão, fechado a modificação).

**Total calculado, nunca persistido.** Não existe coluna de total. O
`ContractResource` resolve o `ContractPricingService` do container, chama
`calculate($contract)` e expõe subtotal, ajustes e total já em reais. Assim o
valor nunca fica "velho" em relação aos itens/regras.

**Regras comutativas.** Cada regra calcula seu desconto a partir do
`result->subtotal` (o subtotal original, que é preservado ao longo da cadeia),
não do total acumulado. Logo a ordem de aplicação não altera o resultado e os
descontos somam sobre a mesma base.

**Regra de status (cancelamento).** `ContractPolicy` bloqueia `update`,
`addItem` e `removeItem` quando o contrato está `cancelled`, retornando 403. A
Policy é a fonte única da regra e é aplicada via `Gate::authorize` tanto no
controller web quanto no de API; o `delete` (soft delete) permanece permitido.

## 5. Decisões técnicas

- **Parametrização das regras.** Limiares e percentuais são parâmetros do
  construtor de cada regra, com defaults; hoje são registrados com esses defaults
  no `PricingServiceProvider`. Permite variar a configuração sem mexer na lógica.
- **Arredondamento.** O desconto é `(int) round(subtotal * pct / 100)` — arredonda
  ao centavo mais próximo antes de virar `Adjustment`, mantendo tudo inteiro.
- **Validação de CPF/CNPJ.** A Rule `CpfCnpj` normaliza para dígitos, decide
  CPF (11) ou CNPJ (14) pelo tamanho, rejeita sequências repetidas e confere os
  **dois dígitos verificadores** (módulo 11) — não valida apenas formato/tamanho.
- **Item duplicado → 422.** O `StoreContractItemRequest` valida
  `unique(contract_id, service_id)`, devolvendo erro de validação (422) com
  mensagem em pt-BR, respeitando a constraint do banco em vez de depender só dela.
- **Formato de dinheiro na API.** Cada recurso expõe o valor em **centavos**
  (inteiro, para cálculo/consumo programático) e também uma **string formatada**
  em reais (apresentação), ex.: `base_price_cents: 15000` e `base_price: "150.00"`.
- **Mensagens do validator em pt-BR** via `lang/pt_BR/validation.php` (com nomes
  amigáveis de atributos) e locale `pt_BR`.
- **Testes sobre PostgreSQL.** A suíte roda no mesmo SGBD de produção (banco
  separado), evitando divergências de dialeto (a busca usa `lower(col) like ?`,
  portável entre SGBDs).

## 6. O que melhoraria com mais tempo

Itens não implementados e como eu encaminharia:

- **Histórico/versionamento do contrato** — hoje não há trilha de alterações.
  Faria com um observer gravando snapshots dos itens/valores a cada mudança, ou
  com `spatie/laravel-activitylog`.
- **Edição de item in-place** — atualmente alterar um item é remover e adicionar
  de novo. Acrescentaria um endpoint `updateItem` (PATCH) para mudar
  quantidade/preço sem recriar.
- **Parâmetros das regras vindos de banco/config** — os limiares/percentuais
  estão hardcoded nos defaults registrados no provider. Moveria para
  configuração/persistência para permitir ajuste sem deploy.
- **Value Object `Money`** — hoje há um helper de apresentação (`app/Support/Money`,
  centavos → string). Um VO encapsulando os centavos (com soma, percentual,
  arredondamento) deixaria a aritmética monetária mais segura e expressiva.
- **CNPJ alfanumérico** (vigente a partir de jul/2026) — a Rule atual trata o
  documento como numérico. Suportaria os caracteres alfanuméricos aplicando o
  mesmo módulo 11 sobre o valor ASCII de cada posição.
- **CHECK de não-negatividade no Postgres** — garantir no schema que
  `quantity`/`unit_price`/`base_price` são `>= 0`, além da validação na aplicação.
- **Separar PK interna de identificador público** — manter a PK e expor um
  identificador público distinto, caso se queira desacoplar o id de banco da URL.
- **Mais cobertura de testes** — testes end-to-end de frontend e ampliar os
  cenários de feature da API.
