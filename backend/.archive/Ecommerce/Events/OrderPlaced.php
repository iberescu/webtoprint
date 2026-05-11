<?php

namespace Modules\Ecommerce\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Vanilo\Order\Contracts\Order;

/**
 * Fires after PlaceOrder converts a cart to a Vanilo order.
 * Distribution listens here to spawn ProductionJobs (spec §13 step 10).
 */
class OrderPlaced
{
    use Dispatchable;

    public function __construct(public readonly Order $order)
    {
    }
}
