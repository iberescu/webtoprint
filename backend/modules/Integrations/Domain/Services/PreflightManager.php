<?php

namespace Modules\Integrations\Domain\Services;

use Illuminate\Contracts\Container\Container;
use Modules\Integrations\Domain\Contracts\PreflightDriver;

class PreflightManager
{
    /** @var array<string,class-string<PreflightDriver>> */
    private array $drivers = [];
    private ?string $default = null;

    public function __construct(private readonly Container $app)
    {
    }

    public function register(string $key, string $class, bool $isDefault = false): void
    {
        $this->drivers[$key] = $class;
        if ($isDefault) {
            $this->default = $key;
        }
    }

    public function driver(?string $key = null): PreflightDriver
    {
        $key ??= $this->default;
        if (! $key || ! isset($this->drivers[$key])) {
            throw new \RuntimeException('No preflight driver registered.');
        }
        return $this->app->make($this->drivers[$key]);
    }
}
