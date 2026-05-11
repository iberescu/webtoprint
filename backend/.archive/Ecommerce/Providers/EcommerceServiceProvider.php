<?php

namespace Modules\Ecommerce\Providers;

use App\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Ecommerce\Events\OrderPlaced;
use Modules\Ecommerce\Listeners\HandOffOrderToDistribution;

class EcommerceServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Ecommerce';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }

    public function boot(): void
    {
        parent::boot();

        // Adapter wiring: Ecommerce knows about Distribution, Distribution
        // does NOT know about Ecommerce. Replacing this module with a
        // Shopify/Magento adapter is just registering a different listener.
        Event::listen(OrderPlaced::class, HandOffOrderToDistribution::class);
    }
}
