<?php

namespace Modules\Distribution\Providers;

use App\Modules\ModuleServiceProvider;

class DistributionServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Distribution';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
