<?php

namespace Modules\Designer\Providers;

use App\Modules\ModuleServiceProvider;

class DesignerServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Designer';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
