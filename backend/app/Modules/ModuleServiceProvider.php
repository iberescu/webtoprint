<?php

namespace App\Modules;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Base class for module service providers. Centralises the module
 * boot conventions: migrations, routes, config, factories, translations.
 *
 * A module subclass declares its short name and the framework wires up
 * the rest from a predictable directory layout.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Short module name, e.g. "PIM". Used to namespace config and translations. */
    abstract protected function name(): string;

    /** Absolute path to the module root (Domain, Http, Database, …). */
    abstract protected function path(): string;

    public function register(): void
    {
        $config = $this->path() . '/config.php';
        if (file_exists($config)) {
            $this->mergeConfigFrom($config, strtolower($this->name()));
        }

        $this->registerBindings();
    }

    public function boot(): void
    {
        $migrations = $this->path() . '/Database/Migrations';
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }

        $this->loadRoutes();
    }

    /** Override to register bindings, listeners, observers. */
    protected function registerBindings(): void
    {
        //
    }

    protected function loadRoutes(): void
    {
        $apiRoutes = $this->path() . '/Http/routes-api.php';
        if (file_exists($apiRoutes)) {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group($apiRoutes);
        }

        $webRoutes = $this->path() . '/Http/routes-web.php';
        if (file_exists($webRoutes)) {
            Route::middleware('web')->group($webRoutes);
        }
    }
}
