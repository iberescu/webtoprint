<?php

use Modules\PIM\Domain\Models\Product;
use Modules\Pricing\Domain\Contracts\PriceCalculatorInterface;
use Modules\Pricing\Domain\Services\CalculatorRegistry;
use Modules\Pricing\Domain\Services\DefaultPriceCalculator;
use Modules\Pricing\Domain\ValueObjects\PriceResult;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;

class FreeFlyerCalculator implements PriceCalculatorInterface
{
    public function calculate(ProductConfiguration $configuration): PriceResult
    {
        return new PriceResult(true, 'EUR', 0, 0, 0, [['label' => 'On the house', 'amount' => 0]]);
    }
}

it('returns the default calculator when no slug is registered', function () {
    $registry = app(CalculatorRegistry::class);
    $product = new Product(['slug' => 'unmatched']);
    expect($registry->for($product))->toBeInstanceOf(DefaultPriceCalculator::class);
});

it('returns the slug-specific calculator when one is registered', function () {
    $registry = app(CalculatorRegistry::class);
    $registry->register('flyer', FreeFlyerCalculator::class);

    $product = new Product(['slug' => 'flyer']);
    expect($registry->for($product))->toBeInstanceOf(FreeFlyerCalculator::class);

    $other = new Product(['slug' => 'poster']);
    expect($registry->for($other))->toBeInstanceOf(DefaultPriceCalculator::class);
});

it('throws if no default and no per-slug binding is configured', function () {
    $registry = new CalculatorRegistry(app());
    $registry->for(new Product(['slug' => 'anything']));
})->throws(RuntimeException::class, 'No price calculator');
