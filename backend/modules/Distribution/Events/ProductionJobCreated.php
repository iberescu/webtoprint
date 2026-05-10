<?php

namespace Modules\Distribution\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Distribution\Domain\Models\ProductionJob;

class ProductionJobCreated
{
    use Dispatchable;

    public function __construct(public readonly ProductionJob $job)
    {
    }
}
