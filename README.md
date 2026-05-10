# Web-to-Print Platform

Self-hosted web-to-print platform for printing companies. One install per customer.

This repository is the reusable core platform; per-customer overrides live in `custom/<customer>/`.

See [`STEPS.md`](STEPS.md) for the implementation step plan and current progress.
See [`docs/spec.md`](docs/spec.md) for the full MVP technical specification.

## Layout

```
backend/      Laravel API + modular domain (PIM, Pricing, Designer, …)
storefront/   Astro static site with React islands
designer/     Fabric.js online designer (Vite + TS)
packages/     Shared libs (types, api-client, pdf-tools)
custom/       Per-customer overrides (pricing, templates, themes, …)
docker/       Service Dockerfiles + configs
deployment/   Production deployment helpers
docs/         Specs and architecture notes
```

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
| Ecommerce | Vanilo-based cart/order/checkout adapter | All print-domain modules |

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
