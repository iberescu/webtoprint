<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Auto-discovers module providers under backend/modules/<Module>/Providers
 * and per-customer overrides under custom/<customer>/Providers.
 *
 * A module is independent: it ships its own ServiceProvider that registers
 * its routes, migrations, config, and bindings.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->discoverProviders(base_path('modules'), 'Modules') as $fqcn) {
            $this->app->register($fqcn);
        }

        foreach ($this->discoverProviders(base_path('../custom'), 'Custom') as $fqcn) {
            $this->app->register($fqcn);
        }
    }

    /**
     * Walk <root>/<Module>/Providers/*ServiceProvider.php and yield their FQCNs.
     */
    private function discoverProviders(string $root, string $namespacePrefix): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $providers = [];

        foreach (scandir($root) as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $providersDir = $root . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'Providers';
            if (! is_dir($providersDir)) {
                continue;
            }

            foreach (scandir($providersDir) as $file) {
                if (! str_ends_with($file, 'ServiceProvider.php')) {
                    continue;
                }
                $class = substr($file, 0, -4);
                $providers[] = "{$namespacePrefix}\\{$module}\\Providers\\{$class}";
            }
        }

        return $providers;
    }
}
