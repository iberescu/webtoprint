<?php

namespace Modules\Pricing\Domain\ValueObjects;

use Modules\PIM\Domain\Models\Product;

/**
 * Read-only carrier object passed to PriceCalculatorInterface.
 * Holds the resolved Product plus the user's selected configuration.
 */
final class ProductConfiguration
{
    public function __construct(
        public readonly Product $product,
        public readonly array $configuration,
        public readonly int $quantity,
        public readonly string $currency = 'EUR',
    ) {
    }

    public function get(string $option, mixed $default = null): mixed
    {
        return $this->configuration[$option] ?? $default;
    }
}
