<?php

namespace Modules\Templates\Providers;

use App\Modules\ModuleServiceProvider;

class TemplatesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Templates';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
