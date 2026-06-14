# Teste Effecti — ERP de Contratos e Serviços

Aplicação para gestão de clientes, serviços e contratos, onde o valor de cada
contrato é calculado dinamicamente a partir dos seus itens e de regras de
desconto compostas. O núcleo de precificação fica isolado do framework, em
`app/Domain/Pricing`. A mesma camada de serviço atende as telas web (Inertia) e
uma API REST em JSON.

## Tecnologias

- PHP `^8.3` / Laravel `^13.7`
- Inertia.js (`inertiajs/inertia-laravel ^3.0`) + Vue 3 (`^3.5`, Composition API)
- PostgreSQL 18
- Laravel Fortify (autenticação) e Sanctum (scaffolding de API)
- Pest 4 (testes) e Laravel Pint (formatação)
- Docker / Docker Compose (Postgres 18, Node 24)

## Funcionalidades

Mapeadas ao enunciado e conferidas no código:

- CRUD de **Clientes**, **Serviços** e **Contratos** (web via Inertia + API REST).
- **Gestão de itens do contrato**: adicionar e remover serviços; ao adicionar,
  o `unit_price` é informado ou, se omitido, congela o `base_price` atual do
  serviço.
- **Cálculo dinâmico do valor** do contrato com regras compostas (desconto por
  quantidade e desconto progressivo por faixa); o total nunca é persistido.
- **Listagem de contratos** trazendo os itens e o total calculado; o
  `ContractResource` expõe subtotal, ajustes (label + valor) e total.
- **Regra de contrato cancelado**: contrato `cancelled` não pode ser editado nem
  ter itens adicionados/removidos (403 via Policy, no web e na API); o soft
  delete continua permitido.
- **Validação de CPF/CNPJ** com cálculo dos dígitos verificadores (não só
  formato/tamanho), aceitando com ou sem máscara.
- **Filtros e paginação** server-side nas listagens.
- Valores monetários em **centavos inteiros**; conversão para reais só na borda
  de apresentação.
- **Testes** (Pest) cobrindo o domínio de pricing, a Rule de CPF/CNPJ, os CRUDs
  web e API, filtros e a Policy de cancelamento.
- **Docker** com boot turnkey (ver abaixo).

## Como rodar

Pré-requisito: Docker.

```bash
docker compose up
```

No primeiro boot o container `app` cria o `.env` (a partir do `.env.example`),
roda `composer install`, gera a `APP_KEY` (se vazia), aplica as migrations e o
seeder e sobe o servidor. O container `vite` instala as dependências do front e
sobe o dev server. **O primeiro boot demora** por causa do `composer install` e
do `npm install`.

- App: http://localhost:8000 (a raiz redireciona para a tela de login)
- Vite (dev server): http://localhost:5173

### Banco de testes

Além do banco da aplicação (`teste_effecti`, criado via `POSTGRES_DB`), o banco
usado pelos testes (`teste_effecti_testing`, configurado no `phpunit.xml`) é
criado automaticamente na primeira inicialização do Postgres, pelo script
`docker/postgres/init.sql`.

Esse script só roda quando o volume do Postgres é criado do zero. Se você já tem
um volume antigo (sem o banco de testes), crie-o manualmente uma vez:

```bash
docker compose exec pgsql createdb -U effecti teste_effecti_testing
```

## Acesso

A tela de login já vem pré-preenchida com as credenciais do usuário criado pelo
seeder:

| E-mail | Senha |
|---|---|
| `admin@effecti.com` | `password` |

O seeder também popula 5 clientes, 5 serviços e um contrato de exemplo cujos
itens disparam os dois descontos (subtotal R$ 1.300,00 → 10% progressivo + 5%
por quantidade), para visualizar o cálculo já populado. O seeder é idempotente.

## Testes

```bash
docker compose exec app php artisan test
```

Os testes rodam sobre PostgreSQL, em um banco separado (`teste_effecti_testing`),
para manter paridade com produção.

## API REST

A API espelha a camada web sobre os mesmos serviços/regras, sob o prefixo `/api`,
retornando API Resources:

- `GET|POST /api/customers`, `GET|PUT|DELETE /api/customers/{customer}`
- `GET|POST /api/services`, `GET|PUT|DELETE /api/services/{service}`
- `GET|POST /api/contracts`, `GET|PUT|DELETE /api/contracts/{contract}`
- `POST /api/contracts/{contract}/items`, `DELETE /api/contracts/{contract}/items/{item}`

A API não exige autenticação (decisão para facilitar a avaliação). Valores
monetários são enviados e devolvidos em **centavos** (inteiros). `status` aceita
`active`/`inactive` (cliente) e `active`/`cancelled` (contrato). O documento do
cliente é validado (CPF/CNPJ) e aceito com ou sem máscara.

### Exemplos (curl)

Base: `http://localhost:8000`. Os IDs são UUIDs — substitua os `{...}` pelos
valores retornados nas respostas.

**Listar clientes** (com filtro e paginação opcionais):

```bash
curl -s "http://localhost:8000/api/customers?search=acme&status=active" \
  -H "Accept: application/json"
```

**Criar cliente:**

```bash
curl -s -X POST http://localhost:8000/api/customers \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
    "name": "Acme Ltda",
    "federal_document": "11.222.333/0001-81",
    "email": "contato@acme.com",
    "status": "active"
  }'
```

**Atualizar / remover cliente** (soft delete):

```bash
curl -s -X PUT http://localhost:8000/api/customers/{customer_id} \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Acme S.A.","federal_document":"11222333000181","email":"contato@acme.com","status":"inactive"}'

curl -s -X DELETE http://localhost:8000/api/customers/{customer_id} \
  -H "Accept: application/json"
```

**Criar serviço** (`base_price` em centavos — R$ 150,00 = `15000`):

```bash
curl -s -X POST http://localhost:8000/api/services \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Consultoria","base_price":15000}'
```

**Criar contrato:**

```bash
curl -s -X POST http://localhost:8000/api/contracts \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
    "customer_id": "{customer_id}",
    "start_date": "2026-01-01",
    "end_date": null,
    "status": "active"
  }'
```

**Adicionar item ao contrato** (`unit_price` opcional; se omitido, congela o
`base_price` atual do serviço):

```bash
curl -s -X POST http://localhost:8000/api/contracts/{contract_id}/items \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"service_id":"{service_id}","quantity":3,"unit_price":30000}'
```

**Ver o contrato com o cálculo de preço** (subtotal, ajustes e total):

```bash
curl -s "http://localhost:8000/api/contracts/{contract_id}" \
  -H "Accept: application/json"
```

Trecho da resposta (`pricing`):

```json
{
  "data": {
    "pricing": {
      "subtotal_cents": 90000,
      "subtotal": "900.00",
      "adjustments": [
        { "label": "Desconto por quantidade (5%)", "amount_cents": -4500, "amount": "-45.00" },
        { "label": "Desconto progressivo (5%)", "amount_cents": -4500, "amount": "-45.00" }
      ],
      "total_cents": 81000,
      "total": "810.00"
    }
  }
}
```

**Remover item do contrato:**

```bash
curl -s -X DELETE http://localhost:8000/api/contracts/{contract_id}/items/{item_id} \
  -H "Accept: application/json"
```

## Documentação técnica

Decisões de arquitetura, modelagem e regras de negócio estão em
[TECHNICAL.md](TECHNICAL.md).
