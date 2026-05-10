<?php

namespace Modules\Auth\Providers;

use App\Modules\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Auth';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
