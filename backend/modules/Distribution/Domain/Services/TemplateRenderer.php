<?php

namespace Modules\Distribution\Domain\Services;

use Illuminate\Support\Facades\Blade;

/**
 * Renders editable Blade templates for jobsheets / JDF / MXML / file naming.
 *
 * Resolution order (lets `custom/<customer>/templates/...` override core):
 *   1. custom/<customer>/templates/<key>
 *   2. modules/Distribution/Templates/<key>
 */
class TemplateRenderer
{
    public function render(string $templateKey, array $vars): string
    {
        $path = $this->resolve($templateKey);
        $contents = file_get_contents($path);
        return Blade::render($contents, $vars);
    }

    private function resolve(string $templateKey): string
    {
        $customer = config('app.customer', 'customer');
        $candidates = [
            base_path("../custom/{$customer}/templates/{$templateKey}"),
            base_path("modules/Distribution/Templates/{$templateKey}"),
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }
        throw new \RuntimeException("Template not found: {$templateKey}");
    }
}
