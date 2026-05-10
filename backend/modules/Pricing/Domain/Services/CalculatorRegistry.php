<?php

namespace Modules\Pricing\Domain\Services;

use Illuminate\Contracts\Container\Container;
use Modules\PIM\Domain\Models\Product;
use Modules\Pricing\Domain\Contracts\PriceCalculatorInterface;

/**
 * Resolves the right calculator for a given product.
 *
 * Resolution order:
 *   1. Explicit per-slug binding (set via ::register('flyer', FooCalculator::class))
 *   2. Per-customer override resolved from custom/<customer>/pricing/<Slug>PriceCalculator.php
 *      (the customisation layer service provider can call ::register())
 *   3. Default calculator
 */
class CalculatorRegistry
{
    /** @var array<string,class-string<PriceCalculatorInterface>> */
    private array $bySlug = [];

    /** @var class-string<PriceCalculatorInterface>|null */
    private ?string $default = null;

    public function __construct(private readonly Container $app)
    {
    }

    public function register(string $productSlug, string $calculatorClass): void
    {
        $this->bySlug[$productSlug] = $calculatorClass;
    }

    public function setDefault(string $calculatorClass): void
    {
        $this->default = $calculatorClass;
    }

    public function for(Product $product): PriceCalculatorInterface
    {
        $class = $this->bySlug[$product->slug] ?? $this->default;
        if (! $class) {
            throw new \RuntimeException('No price calculator registered (and no default set).');
        }
        /** @var PriceCalculatorInterface $instance */
        $instance = $this->app->make($class);
        return $instance;
    }
}
