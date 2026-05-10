<?php

namespace Modules\Settings\Providers;

use App\Modules\ModuleServiceProvider;
use Modules\Settings\Domain\Services\SettingsRepository;

class SettingsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Settings';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(SettingsRepository::class);
    }
}
