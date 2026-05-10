<?php

namespace Modules\PIM\Providers;

use App\Modules\ModuleServiceProvider;

class PIMServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'PIM';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
