<?php

namespace Custom\customer\Providers;

use App\Modules\ModuleServiceProvider;
use Custom\customer\Pricing\FlyerPriceCalculator;
use Modules\Pricing\Domain\Services\CalculatorRegistry;

/**
 * Per-customer customisation entry point. Anything in custom/<customer>/
 * is owned by the deploy site, not the platform.
 *
 * Auto-loaded by ModulesServiceProvider.
 */
class CustomerServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'customer';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }

    public function boot(): void
    {
        parent::boot();

        // Register customer-specific calculators (overrides the default).
        $registry = $this->app->make(CalculatorRegistry::class);
        $registry->register('flyer', FlyerPriceCalculator::class);
    }
}
