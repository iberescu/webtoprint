# Implementation Steps

Each step is independent, modular, and produces something testable.
Mark a step `[x]` when its acceptance criteria pass.

## Step 0 — Foundation
- [x] Top-level directory layout (spec §5)
- [x] `docker-compose.yml` with all services (spec §4 infra)
- [x] Backend Dockerfile + nginx config
- [x] Laravel slim bootstrap (composer.json, bootstrap, routes, config)
- [x] Module auto-loader service provider
- [x] Root README + this STEPS file

**Acceptance:** `docker compose build` resolves, `composer install && php artisan --version` works inside the php container.

## Step 1 — Core module
- [x] `Module` base service provider that registers migrations, routes, config from a module dir
- [x] Standard error response shape (spec §14)
- [x] OpenAPI scaffolding stubs (`/api/v1`)
- [x] Health endpoint `GET /api/v1/health`

**Acceptance:** `GET /api/v1/health` returns `{status: ok}`; thrown `ValidationException` returns spec §14 error body.

## Step 2 — Auth + Settings
- [ ] Admin user model + migration
- [ ] Sanctum personal-access-token guard
- [ ] Login / logout / me endpoints
- [ ] Company settings KV table + admin-only update API

**Acceptance:** Admin can log in, hit `me`, and read/write company settings.

## Step 3 — FileStorage
- [x] `files` table migration (spec §11)
- [x] `File` model + repository
- [x] Direct-upload presign endpoint (S3/MinIO)
- [x] File download / signed URL endpoint
- [x] Checksum + size validation
- [x] Abandoned-file cleanup command (scheduled)

**Acceptance:** Browser-direct upload to MinIO works; record persisted; signed URL serves the file.

## Step 4 — PIM A: products + categories
- [x] `products`, `product_categories`, `product_assets`, `product_versions`, `product_template_bindings` migrations
- [x] Eloquent models + factories
- [x] Admin REST CRUD: categories, products
- [x] Public read endpoints: `GET /api/v1/products`, `GET /api/v1/products/{slug}`

**Acceptance:** Create a product via API, fetch by slug, snapshot version saved on update.

## Step 5 — PIM B: options + values + rules + configurator
- [x] `product_options`, `product_option_values`, `product_rules` migrations
- [x] Models + factories
- [x] Admin CRUD for options / values / rules
- [x] `GET /api/v1/products/{slug}/configurator`
- [x] `POST /api/v1/products/{slug}/validate` (whitelist + blacklist evaluation)

**Acceptance:** §7 examples in spec produce the documented JSON; invalid combos yield `combination_not_allowed`.

## Step 6 — Pricing engine
- [ ] `price_lists`, `price_rules`, `price_tables`, `price_table_rows`, `price_modifiers` migrations
- [ ] `PriceCalculatorInterface` (spec §7)
- [ ] Default calculator: base table + quantity breaks + option modifiers + setup fees + min price
- [ ] Calculator registry → resolves per-product custom calculator from `custom/customer/pricing/`
- [ ] `POST /api/v1/products/{slug}/price`

**Acceptance:** §7 example request returns the example response shape with `breakdown` lines.

## Step 7 — Designer backend
- [ ] `design_templates`, `designs` migrations (spec §8)
- [ ] CRUD APIs (template admin + design end-user)
- [ ] Preview generation queued job (PDF-LIB / Imagick)
- [ ] Print PDF generation queued job
- [ ] PDF upload + validation endpoints

**Acceptance:** Save a design → get preview → generate print PDF → status transitions match spec §8.

## Step 8 — Ecommerce (Vanilo-backed adapter)
- [x] customers + customer_addresses migrations (kept)
- [x] Vanilo (cart/order/checkout/payment) added via composer
- [x] `Modules\PIM\Domain\Models\Product` implements `Vanilo\Contracts\Buyable`
- [x] Storefront cart/checkout APIs delegate to Vanilo
- [x] **Distribution stays ecommerce-agnostic** — `ProductionJobInput` DTO is the only contract
- [x] `Modules\Ecommerce\Listeners\HandOffOrderToDistribution` is the Vanilo→Distribution adapter
- [x] Shopify + Magento adapter scaffolds in `custom/customer/Integrations/`
- [x] Architecture documented in `docs/architecture.md`

**Acceptance:** Add configured product to cart → checkout → Vanilo order created → `OrderPlaced` fires → adapter calls `Distribution::CreateProductionJob`. Disabling the Ecommerce module and registering the Shopify webhook produces the same downstream result.

## Step 9 — Distribution
- [ ] `production_jobs` migration
- [ ] Jobsheet PDF generator (Blade → Dompdf/Browsershot)
- [ ] JDF generator (Twig template)
- [ ] MXML generator (Twig template)
- [ ] Package ZIP builder (artwork + jobsheet + jdf + mxml + metadata + preview)
- [ ] APIs for create / regenerate / download

**Acceptance:** §13 workflow steps 10–15 produce a downloadable ZIP matching §9 structure.

## Step 10 — Templates
- [ ] Editable templates admin (jobsheet/jdf/mxml/folder/file)
- [ ] Template version history
- [ ] Per-product or global template selection

**Acceptance:** Admin edits jobsheet template → next package generation uses the new version.

## Step 11 — Integrations
- [ ] Stripe payment driver
- [ ] PayPal driver
- [ ] Bank transfer / manual invoice driver
- [ ] callas pdfToolbox driver (CLI wrapper)

**Acceptance:** Each driver implements a common `PaymentGateway` / `PreflightDriver` contract.

## Step 12 — Filament admin
- [ ] Resources for every spec §16 screen
- [ ] Role-based access
- [ ] Dashboard widgets (orders, jobs, revenue)

**Acceptance:** All §16 screens exist and CRUD against the API.

## Step 13 — Storefront (Astro)
- [ ] Static pages (home, category, product, info)
- [ ] React islands: configurator, pricing, cart, checkout, designer-launcher, pdf-upload
- [ ] API client package

**Acceptance:** Product page flow §10 works against the live backend.

## Step 14 — Designer frontend (Fabric.js)
- [ ] Editor shell (Vite + TS)
- [ ] Page model with bleed / safe-margin overlays
- [ ] Editable text / image placeholders
- [ ] Preview + approve flow → backend

**Acceptance:** Edit a template → save → preview → approve → cart item carries `print_pdf_file_id`.

## Step 15 — Customization layer
- [ ] Auto-discover `custom/customer/*` providers
- [ ] Override calculator / rule / template / theme

**Acceptance:** Drop a `FlyerPriceCalculator` in `custom/customer/pricing/` → it overrides the default.

## Step 16 — Prepress hardening (callas)
- [ ] Artwork preflight job
- [ ] `preflight_*` columns on artwork
- [ ] Block checkout/production on `failed`

**Acceptance:** Bad PDF → `failed`; good PDF → `passed`; report file stored.

## Step 17 — Seeders
- [ ] Flyer, Business Card, Poster with full options + price tables + sample rules

**Acceptance:** `php artisan db:seed` produces a working storefront catalog.

## Step 18 — End-to-end tests
- [ ] Pest/PHPUnit feature test that walks §19 completion criteria 1–21

**Acceptance:** `php artisan test` passes the full MVP scenario.
