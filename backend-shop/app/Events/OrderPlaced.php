<?php

namespace Shop\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Vanilo\Order\Contracts\Order;

class OrderPlaced
{
    use Dispatchable;

    public function __construct(public readonly Order $order)
    {
    }
}
