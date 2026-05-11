# Web-to-Print Platform

Self-hosted web-to-print platform for printing companies. One install per customer.

This repository is the reusable core platform; per-customer overrides live in `custom/<customer>/`.

See [`STEPS.md`](STEPS.md) for the implementation step plan and current progress.
See [`docs/spec.md`](docs/spec.md) for the full MVP technical specification.
See [`CHANGELOG.md`](CHANGELOG.md) for a running history of what got built, why, and the trade-offs along the way (most-recent first).

## Layout

```
backend/        Print Laravel API — PIM, Pricing, Designer, Distribution, …
                (port 8000)
backend-shop/   Shop Laravel API — Vanilo cart/order/checkout/customer auth
                (port 8001). Talks to backend over HTTP via
                /api/v1/internal/* and a shared bearer token.
storefront/     Astro static site with React islands.
designer/       Fabric.js online designer (Vite + TS).
packages/       Shared libs (types, api-client, pdf-tools).
custom/         Per-customer overrides (pricing, templates, themes, …).
docker/         Service Dockerfiles + configs.
deployment/     Production deployment helpers.
docs/           Specs and architecture notes.
```

The print and shop apps are deployable independently. Shop reuses backend's
installed `vendor/` in dev (cheap path reference) but its own `Shop\`
namespace, own SQLite DB, own bootstrap. Swapping Vanilo for Shopify or
Magento means replacing backend-shop with a different adapter — the print
backend never imports Vanilo classes. See [CHANGELOG.md](CHANGELOG.md)
under "Print/shop service split" for the boundary contract.

## Quick start (development)

```bash
cp .env.example .env
docker compose up -d
docker compose exec php composer install
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate --seed
```

Storefront and designer have their own `package.json` and run via `pnpm dev`.

## Modules

Each backend module is independent — its own service provider, migrations, routes, and tests:

| Module | Purpose | Depends on |
|---|---|---|
| Core | Module loader, error shape, OpenAPI, base classes | — |
| Auth | Admin authentication (Sanctum) | Core |
| Settings | Company / system settings KV | Core |
| FileStorage | Centralised files table + S3/MinIO uploads | Core |
| PIM | Products, categories, options, values, rules | Core, FileStorage |
| Pricing | Price tables, calculator interface, modifiers | Core, PIM |
| Designer | Design templates, designs, PDF generation | Core, PIM, FileStorage |
| Distribution | Production jobs, jobsheet/MXML/JDF/package | Core, FileStorage |
| Templates | Editable Blade/Twig templates for distribution | Core |
| Integrations | Stripe, PayPal, callas pdfToolbox drivers | Core, FileStorage |
| InternalBridge | Token-gated `/api/v1/internal/*` API for the shop service | PIM, Designer, Distribution |

The ecommerce adapter (Vanilo cart/order/checkout) lives in **backend-shop/**
as a separate Laravel app — see [CHANGELOG.md](CHANGELOG.md) for the split
rationale and the boundary contract.

### Boundary rule

The print-domain modules (PIM, Pricing, Designer, Distribution) are **ecommerce-agnostic**. Replacing Vanilo with Shopify or Magento is a matter of swapping the Ecommerce adapter — see [`docs/architecture.md`](docs/architecture.md). `ProductionJobInput` is the only DTO that crosses the boundary.

## Customization layer

Drop overrides in `custom/<customer>/` to change behaviour without forking core:

- `pricing/` — custom `PriceCalculatorInterface` implementations
- `distribution/` — custom output drivers
- `templates/` — overridden jobsheet / JDF / MXML templates
- `themes/` — storefront theme
- `integrations/` — customer-specific MIS integrations
- `config/` — module configuration overrides

## Status

This is an in-progress MVP build. See `STEPS.md` for what is shipped vs. stubbed.
