# Architecture

## Module dependency rules

The print-domain modules and the ecommerce layer are kept on opposite sides of a hard boundary so the platform stays portable across cart systems.

```
┌──────────────────────────────────────────────────────────────┐
│                    Print domain (always present)             │
│                                                              │
│   PIM ──► Pricing                                            │
│   PIM ──► Designer                                           │
│   PIM ──► Distribution                                       │
│   Designer ──► Distribution (artwork file references)        │
│                                                              │
│   All four also use FileStorage (shared platform infra).     │
└────────────────────────────────────────▲─────────────────────┘
                                         │  ProductionJobInput DTO
                                         │  (the only contract crossing the line)
┌────────────────────────────────────────┴─────────────────────┐
│                  Ecommerce layer (swappable)                 │
│                                                              │
│   • Vanilo adapter (Modules\Ecommerce)                       │
│   • Shopify adapter (custom/<customer>/Integrations/Shopify) │
│   • Magento adapter (custom/<customer>/Integrations/Magento) │
│   • Manual API call (POST /api/v1/production/jobs)           │
└──────────────────────────────────────────────────────────────┘
```

Inviolable rules:

- `Modules/PIM`, `Modules/Pricing`, `Modules/Designer`, `Modules/Distribution` MUST NOT import any class from `Modules/Ecommerce` or any third-party ecommerce SDK.
- `Modules/Ecommerce` MAY import from any print-domain module (it's the adapter).
- All ecommerce → print-domain communication goes through `Modules\Distribution\Application\Actions\CreateProductionJob` with a `ProductionJobInput` DTO.
- Print-domain → ecommerce communication goes through Distribution events (`ProductionJobCreated`, `ProductionPackageGenerated`). Adapters listen, modules don't dispatch into ecommerce.

A pre-commit / CI grep enforces it:

```bash
! grep -R "Modules\\\\Ecommerce" backend/modules/PIM backend/modules/Pricing \
                                  backend/modules/Designer backend/modules/Distribution
```

## Swapping the ecommerce layer

### To replace Vanilo with Shopify

1. Disable `Modules\Ecommerce\Providers\EcommerceServiceProvider` (rename or remove from `modules/`).
2. Enable the Shopify adapter — add a route in your customer service provider:

   ```php
   Route::post('/webhooks/shopify/orders', [ShopifyOrderWebhookController::class, 'handle']);
   ```

3. Configure the Shopify webhook URL and HMAC secret.
4. Remove Vanilo from `composer.json`.

The print-domain modules don't change.

### To replace with Magento

Same shape — register `MagentoOrderWebhookController::handle` for the Magento order_save webhook.

### Pure-headless / manual hand-off

Any external system can POST to `/api/v1/production/jobs` with the `ProductionJobInput` payload. Useful for MIS systems that already place orders elsewhere.

## What lives where

| Concern | Owner | Notes |
|---|---|---|
| Product catalogue + options + rules | PIM | Always platform-side. Shopify/Magento variants get *mapped* to PIM products at order time. |
| Pricing | Pricing | Always platform-side; configurable products price differently per customer. Custom calculators per slug under `custom/<customer>/Pricing`. |
| Online editor + design files | Designer | Talks to FileStorage. Doesn't know who the customer is. |
| Production jobs, jobsheet, JDF, MXML, ZIP | Distribution | Only carries `external_order_ref` + a JSON snapshot. Self-contained. |
| Cart / checkout / payment / order state | Ecommerce adapter | Vanilo today; replace at deploy time. |
| Files (uploads, artefacts, packages) | FileStorage | Shared platform infra. |
| Templates (jobsheet/jdf/mxml editing) | Templates | Per-deploy editable; falls back to module defaults. |

## ProductionJobInput contract

This is the only DTO that crosses the boundary.

```php
new ProductionJobInput(
    externalOrderRef:        '2026-000123',     // string
    externalOrderItemRef:    '7',                // string
    source:                  'vanilo',           // string tag
    productName:             'Flyer',            // string
    productSnapshot:         [...],              // PIM-produced; opaque to Distribution
    configuration:           ['format' => 'a4', 'quantity' => 250],
    quantity:                250,
    artworkFileId:           '01HX...',          // FileStorage id, nullable
    customerSnapshot:        ['name'=>..., 'email'=>...],
    shippingAddress:         [...],
    billingAddress:          [...],
    metadata:                [...]               // free-form
);
```

`productSnapshot` is whatever `Modules\PIM\Application\Actions\SnapshotProduct` produces — an opaque blob that the jobsheet template can read for rich detail. Distribution never traverses it.

## Events emitted by Distribution

- `ProductionJobCreated` — fired immediately when a job is persisted. Adapters can hook this to update ecommerce-side status (e.g. mark a Vanilo order as `in_production`).
- `ProductionPackageGenerated` — fired when the ZIP is on storage. Adapters can post tracking back to Shopify, or notify the customer.

Print-domain modules never listen to ecommerce events.
