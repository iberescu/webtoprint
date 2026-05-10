# Customer customization layer

This directory holds **per-deployment** overrides. Each customer gets their own folder under `custom/<slug>/` and the platform auto-discovers their service provider at boot.

## Layout

```
custom/<customer>/
  Providers/         # ServiceProvider that wires the rest in
  Pricing/           # Custom PriceCalculatorInterface implementations
  Distribution/      # Custom output drivers (e.g. MIS-specific exporters)
  Templates/         # Overridden jobsheet/jdf/mxml/file_name templates
  themes/            # Storefront theme overrides
  Integrations/      # Customer-specific MIS / shipping / accounting integrations
  config/            # Module-level overrides
```

## How discovery works

`App\Providers\ModulesServiceProvider` walks `custom/<customer>/Providers` and registers every `*ServiceProvider.php` it finds. From there, the customer provider can:

- Bind concrete classes into the container (e.g. a custom `PriceCalculatorInterface`)
- Register routes / migrations / config (it extends `App\Modules\ModuleServiceProvider`)
- Listen to platform events
- Register Filament resources for custom screens

## Templates

`Modules\Distribution\Domain\Services\TemplateRenderer` looks for templates in this order:

1. `custom/<customer>/templates/<key>`
2. `modules/Distribution/Templates/<key>`

Drop a file with the same name to override only that artefact.

## Pricing

The customer provider can call `CalculatorRegistry::register('flyer', FlyerCalculator::class)` to override pricing for any specific product slug. The default calculator is used otherwise.

See `customer/Pricing/FlyerPriceCalculator.php` for a worked example (10% loyalty discount).
