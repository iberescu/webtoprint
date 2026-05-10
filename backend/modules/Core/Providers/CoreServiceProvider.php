<?php

namespace Modules\Core\Providers;

use App\Modules\ModuleServiceProvider;

class CoreServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Core';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
