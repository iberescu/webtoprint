<?php

namespace Modules\Pricing\Providers;

use App\Modules\ModuleServiceProvider;
use Modules\Pricing\Domain\Contracts\PriceCalculatorInterface;
use Modules\Pricing\Domain\Services\CalculatorRegistry;
use Modules\Pricing\Domain\Services\DefaultPriceCalculator;

class PricingServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Pricing';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(CalculatorRegistry::class, function ($app) {
            $registry = new CalculatorRegistry($app);
            $registry->setDefault(DefaultPriceCalculator::class);
            return $registry;
        });

        $this->app->bind(PriceCalculatorInterface::class, DefaultPriceCalculator::class);
    }
}
