# Teste Effecti — ERP de Contratos e Serviços

Laravel 12 + Inertia + Vue 3 (Composition API) + PostgreSQL, com Pest e Pint.
O núcleo de precificação (`app/Domain/Pricing`) calcula o total do contrato a
partir de regras compostas (desconto por quantidade e desconto progressivo),
sempre de forma dinâmica — o total nunca é persistido.

## Como rodar

Pré-requisito: Docker.

```bash
docker compose up
```

O container `app` é turnkey: cria o `.env` (a partir do `.env.example`), instala
as dependências do Composer, gera a `APP_KEY` (se vazia), roda as migrations e o
seeder, e sobe o servidor. O container `vite` instala as dependências do front e
sobe o dev server.

- App: http://localhost:8000 (a raiz cai na tela de login)
- Vite: http://localhost:5173

### Acesso (pré-preenchido na tela de login)

| E-mail | Senha |
|---|---|
| `admin@effecti.com` | `password` |

O seeder também cria clientes, serviços e um contrato de exemplo que dispara os
dois descontos, para visualizar o cálculo de preço já populado.

## Testes

```bash
docker compose exec app php artisan test
```

Os testes rodam sobre PostgreSQL (configurado no `phpunit.xml`).

## Camada de API

Há uma API REST em JSON espelhando os mesmos serviços/regras das telas web,
sob o prefixo `/api` (`/api/customers`, `/api/services`, `/api/contracts` e
`/api/contracts/{contract}/items`), retornando API Resources.
