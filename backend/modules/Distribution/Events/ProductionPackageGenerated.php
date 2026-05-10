<?php

namespace Modules\Distribution\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Distribution\Domain\Models\ProductionJob;

/**
 * Fired after the package ZIP is ready. Adapters (e.g. ecommerce listeners)
 * can subscribe to update their own order status, post a webhook back to
 * Shopify/Magento, etc.
 */
class ProductionPackageGenerated
{
    use Dispatchable;

    public function __construct(public readonly ProductionJob $job)
    {
    }
}
