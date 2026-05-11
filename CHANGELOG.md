# Project history

A running narrative of what got built, why, and the trade-offs along the way.
Most-recent first.

---

## 2026-05-11 — Cart 500 fix + dev-server 100× speedup

### Add-to-cart was returning 500
- `CartController::addItem` and `CheckoutController::placeOrder` were
  declared with `int $cartId`, but the route uses `{cart}` so Laravel's
  implicit binding hands you a `Vanilo\Cart\Models\Cart`. The type
  mismatch threw before the body ever ran. Both signatures now take
  `VaniloCart $cart` directly.
- The price was being read as `$data['price_json']['gross']`, but the
  storefront emits `gross_price` (see `PriceResult`). Falling through to
  `0` meant cart totals were always `€0`. Fixed with
  `gross_price ?? gross ?? 0`.
- Vanilo's `cart_items` table has no `name` column — only `product_id`,
  `product_type`, `quantity`, `price`. The previous response shape leaked
  `name: null`. We now compute it on demand from the morphed product via
  `$item->product->getName()`.

### Why every request was 10-30s
- The storefront, designer, and admin all felt unusable: a POST to
  `/designer/designs` took **11-32 s**, even a flat GET took **10-30 s**.
- DB benchmarks were fine (sub-millisecond). Programmatic kernel dispatch
  was 3 s. So Laravel itself wasn't slow — only HTTP-served requests
  were.
- Root cause: **opcache `validate_timestamps=1` on a Docker-on-Windows
  bind mount.** Every PHP request triggers a stat() on every cached file
  to check mtimes. Through Docker Desktop's Windows ↔ Linux filesystem
  bridge, those stats cost ~10× their normal price — and Laravel touches
  thousands of files per request.
- Fix in `docker/php/Dockerfile.test`: set `opcache.validate_timestamps=0`
  and `revalidate_freq=0`. Trade-off: PHP edits don't auto-reload —
  `docker exec wtp-backend killall php` (or `docker restart`) to pick
  them up.

### SQLite moved off the bind mount
- The dev SQLite lived at `backend/storage/dev.sqlite` (bind-mounted from
  Windows). Each migration write also took the Windows-FS hit, making
  any DB-write endpoint slow.
- Moved to **in-container** `/var/lib/wtp/dev.sqlite`. The container's
  startup command now `mkdir`s the directory, `touch`es the file, runs
  `migrate --force && db:seed --force`. The DB is ephemeral (gone on
  container rm), which is fine for a dev seed — and ~50× faster for
  writes.

### Numbers (after warm-up; first hit per worker is still a cold compile)
| Endpoint                                     | Before     | After   |
|----------------------------------------------|------------|---------|
| GET  /api/v1/products                        | 10-30 s    | ~0.2 s  |
| POST /api/v1/designer/designs                | 11-32 s    | ~0.2 s  |
| POST /api/v1/designer/designs/{id}/approve   | similar    | ~0.2 s  |
| POST /api/v1/designer/designs/{id}/upload-print-pdf | similar | ~0.7 s |
| POST /api/v1/storefront/cart/{id}/items      | 500        | ~0.2 s  |
| POST /api/v1/storefront/checkout/{id}        | n/a        | ~1.5 s  |

### Checkout: flat → Vanilo address translation
- Follow-up after re-testing: passing the storefront's flat address
  (`{name, email, line1, city, postalcode, country_code}`) into Vanilo's
  `OrderFactory` blew up with `Undefined array key "address"` from
  `OrderFactory::createBillpayer` at line 95 — Vanilo expects the
  billpayer to be `{firstname, lastname, email, address: {...}}` with a
  *nested* address sub-array, and addresses themselves to use
  `country_id` + `address` (street) rather than `country_code` + `line1`.
- New private helpers `toVaniloAddress()` and `toVaniloBillpayer()` in
  `PlaceOrder` do the translation. The `name` field gets split on
  whitespace into firstname/lastname (with a `-` fallback for the
  surname, matching Vanilo's "empty becomes -" convention).
- Isolating the shape mapping inside `PlaceOrder` keeps the boundary
  rule intact: when we swap Vanilo for Shopify/Magento, only this file
  changes — the storefront, controllers, and PIM/Pricing/Designer
  modules keep using the flat shape.

### Curl-verified end-to-end flow
Cart → add item (name `"Flyer"`, total €37.49) → checkout → order
created. The address-less variant (`{"payment_method": "manual_invoice"}`)
is also accepted: when `shipping_address`/`billing_address` are absent,
the helpers return `null`, `array_filter` drops the keys, and Vanilo
correctly skips billpayer/shipping creation.

---

## 2026-05-10 — Big content + design polish + designer rebuild

### Catalogue depth (14 products, Vistaprint-aligned EUR pricing)
- Expanded `CatalogueSeeder` from 3 to **14 products** organised across 4 categories:
  - **Cards & Invites:** Standard Business Card, Premium Business Card, Postcard, Greeting Card
  - **Marketing & Promotion:** Flyer, Folded Brochure, Booklet, Vinyl Sticker
  - **Office & Stationery:** Letterhead, Branded Envelope, Notepad, Presentation Folder
  - **Signage & Banners:** Poster, Roll-up Banner
- Each product carries realistic EU pricing tiers (50/100/250/500/1000/2500…),
  per-product modifiers (gold foil, lamination, premium PVC, …), and a sane
  default configuration that always validates.
- Every price table now includes a **catch-all fallback row** so any unmatched
  configuration prices at +50% rather than failing — fixes the
  "Price unavailable for this configuration" UX.
- `seedFlyer` reorders option values so the configurator's defaults
  (`format=a4, colors=4-4`) don't trip the seeded blacklist.

### EU / GDPR / VAT
- New `Settings` module seeded with company identity, EU VAT rates for all 27
  member states + UK/CH/NO, default rate (DE 19%), and GDPR contact details.
- `DefaultPriceCalculator` now reads the VAT rate from `Settings`, with
  per-country resolution via `configuration.shipping_country`.
- Storefront ships a **GDPR cookie consent banner** with three-tier choice
  (Accept all / Essential only / Customize), `localStorage` persistence with
  12-month TTL, and `window.__consent` exposure for analytics opt-in.
- New legal pages: `/legal/privacy`, `/legal/cookies`, `/legal/terms`,
  `/legal/imprint`, `/legal/gdpr`. The footer links to all five plus the
  GDPR data-request shortcut.

### Generated images via Gemini Nano Banana 2
- New `scripts/generate-images.mjs` orchestrates 20 image-generation calls to
  `gemini-3-pro-image-preview`: 1 logo, 1 hero banner, 4 category banners,
  14 product photos.
- Cached: regenerating only fetches images that aren't already on disk.
- All images saved under `storefront/public/images/{,products,banners}/`.
- The storefront renders these images everywhere: header logo, footer logo,
  hero, category cards, bestseller grid, product list grid, and product detail
  hero + thumbnail strip.

### Vistaprint-style storefront redesign
- New `Header.astro` with a top promo bar (gradient indigo→purple), real logo,
  bold sticky nav, sign-in link, and amber CTA cart button.
- Homepage rebuilt with hero (gradient + real photography + floating "24h
  production" trust card), trust strip with category icons, "Shop by category"
  with real banner imagery, "Bestsellers" grid, and a promo strip with linked
  product photos.
- Product list (`/products`) now has a sticky sidebar (categories + "save 20%
  on first order" callout), bigger photo cards with explicit "starting at"
  pricing, and 24h badges.
- Product detail layout split into image gallery + thumbnail strip + trust
  cards on the left, category badge + 4.7★ rating + capability pills + the
  configurator panel on the right, plus a collapsible specs section.
- New `ProductConfigurator.tsx` with selectable tile-style option pickers,
  validation banner, live price card with "X.XX € per piece", expandable
  breakdown (net + VAT + gross + setup + modifiers), and amber CTA.

### Online designer (Canva-/Vistaprint-style rebuild)
- Replaced the minimal Fabric editor with a 4-column layout:
  topbar (brand + undo/redo + save / preview / approve)
  · left tool sidebar (templates, text, uploads, shapes, colors, QR)
  · contextual left panel
  · centre stage with bleed/safe-area overlays
  · right "selected layer" properties panel.
- **Templates panel:** 6 preset designs (Modern Indigo, Bold Amber, Minimal
  Slate, Pastel Pink, Forest, Premium Black). One click applies a flat
  background + heading + sub-heading.
- **Text panel:** add Heading / Subheading / Body, plus quick-add chips for
  common BC fields (Name, Title, Email, Phone, Website).
- **Uploads panel:** local image upload, persisted thumbnail tray.
- **Shapes:** rectangle / circle / triangle / line.
- **Colors:** 24-swatch palette for both document background and selected
  element fill.
- **QR code:** generates a QR from any URL/text and adds it to the canvas.
- **Layer properties (right):** text content, font family, size, weight, fill,
  X/Y, rotation, opacity, bring forward / send back, duplicate, delete.
- **Undo/redo** with up to 60 history entries plus Ctrl+Z / Ctrl+Y / Delete
  shortcuts.
- Save / Preview / Approve fully wired to the existing `DesignerClient` API.

### Production build + perf
- Storefront now builds for production: 23 prerendered HTML pages totalling
  ~10 MB on disk (mostly images).
- Served by nginx in dev so we get realistic numbers:
  - Homepage: **11ms TTFB**, 25 KB
  - Products grid: **18ms TTFB**, 27 KB
  - Product detail: **20-39ms TTFB**, ~13 KB
  - Hero PNG: 47ms / 660 KB · Logo PNG: 27ms / 60 KB
- Backend switched from `php artisan serve` (single-threaded) to
  `php -S 0.0.0.0:8000` with `PHP_CLI_SERVER_WORKERS=8` so the storefront
  build's parallel `getStaticPaths` API calls don't deadlock.

### Admin smoke-test
- Admin login at `/admin/login` (Filament panel) returns 200 with proper CSRF.
- API auth works: `POST /api/v1/admin/auth/login` returns a Sanctum bearer.
- Configured a product end-to-end: list products via admin API, update the
  flyer's description, verified the change is live on the public detail page.
- Filament resources auto-discovered for: PIM Product, Ecommerce Order,
  Distribution ProductionJob, Templates ContentTemplate.

---

## 2026-05-10 (earlier) — Architectural decoupling

- Made `Modules\Distribution` ecommerce-agnostic. Production jobs now reference
  external orders by string (`source` + `external_order_ref` +
  `external_order_item_ref`) — never by FK. The contract crossing the boundary
  is a single DTO: `ProductionJobInput`.
- `Modules\Ecommerce` is now an *adapter*. It listens to its own `OrderPlaced`
  event (Vanilo-backed) and translates each order item into a
  `ProductionJobInput`.
- Drop-in adapters scaffolded:
  - Vanilo (default, in `Modules\Ecommerce`)
  - Shopify webhook (`custom/customer/Integrations/Shopify`)
  - Magento webhook (`custom/customer/Integrations/Magento`)
- Architecture documented at `docs/architecture.md`.

## 2026-05-10 (earliest) — Initial scaffold

- 11 modular Laravel domains (Core, Auth, Settings, FileStorage, PIM, Pricing,
  Designer, Distribution, Templates, Integrations, Ecommerce).
- Vanilo cart/order/checkout/payment integrated; PIM `Product` implements
  `Vanilo\Contracts\Buyable`.
- 44 Pest tests, 154 assertions — covers value objects, rule evaluator, default
  price calculator, calculator registry, snapshot, ProductionJob creation,
  Vanilo flow, Shopify adapter mapping, MVP §19 acceptance.
- Filament admin panel.
- Astro+React storefront with localStorage cart.
- Fabric.js online designer (initial minimal version, since rebuilt — see top).
- Customer customisation layer with worked `FlyerPriceCalculator` example.

---

## URLs to click (current dev stack)

| Service | URL | Notes |
|---|---|---|
| Storefront (production build) | http://localhost:4321 | nginx-served static |
| Online designer (Canva-style) | http://localhost:5173 | Vite dev server |
| Admin panel | http://localhost:8000/admin/login | `admin@example.com` / `password` |
| Backend API | http://localhost:8000/api/v1 | `php -S` with 8 workers |
| MinIO console | http://localhost:9001 | `webtoprint` / `webtoprint-secret` |

## Restart cheatsheet

```bash
# Backend — DB lives in-container at /var/lib/wtp/dev.sqlite (NOT on the
# Windows bind mount: it's ~50× faster for SQLite writes). The container
# touches the file, migrates and seeds on every boot. Opcache has
# validate_timestamps=0 (see Dockerfile.test), so PHP file edits won't
# auto-reload — restart the container or `docker exec wtp-backend
# killall php` to pick them up.
docker rm -f wtp-backend
docker run -d --name wtp-backend --network webtoprint-dev \
  -v "$PWD:/app" -w /app/backend -p 8000:8000 \
  -e PHP_CLI_SERVER_WORKERS=8 webtoprint-test \
  sh -c 'mkdir -p /var/lib/wtp && touch /var/lib/wtp/dev.sqlite \
    && php artisan config:cache && php artisan route:cache \
    && php artisan migrate --force && php artisan db:seed --force \
    && PHP_CLI_SERVER_WORKERS=8 php -S 0.0.0.0:8000 -t public public/index.php'

# Storefront (production)
cd storefront && npx astro build
docker run -d --name wtp-storefront -v "$PWD/dist:/usr/share/nginx/html:ro" -p 4321:80 nginx:alpine

# Designer
docker run -d --name wtp-designer --network webtoprint-dev \
  -v "$PWD/designer:/app" -w /app -p 5173:5173 \
  -e VITE_API_URL=http://localhost:8000/api/v1 node:22-alpine \
  sh -c 'npm install && npx vite --host 0.0.0.0'
```
