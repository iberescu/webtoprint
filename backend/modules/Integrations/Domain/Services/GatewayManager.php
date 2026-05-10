<?php

namespace Modules\Integrations\Domain\Services;

use Illuminate\Contracts\Container\Container;
use Modules\Integrations\Domain\Contracts\PaymentGateway;

class GatewayManager
{
    /** @var array<string,class-string<PaymentGateway>> */
    private array $drivers = [];

    public function __construct(private readonly Container $app)
    {
    }

    public function register(string $key, string $class): void
    {
        $this->drivers[$key] = $class;
    }

    public function driver(string $key): PaymentGateway
    {
        if (! isset($this->drivers[$key])) {
            throw new \RuntimeException("No payment gateway registered for key {$key}");
        }
        return $this->app->make($this->drivers[$key]);
    }

    /** @return array<string> */
    public function available(): array
    {
        return array_keys($this->drivers);
    }
}
