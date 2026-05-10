<?php

namespace Custom\customer\Pricing;

use Modules\Pricing\Domain\Contracts\PriceCalculatorInterface;
use Modules\Pricing\Domain\Services\DefaultPriceCalculator;
use Modules\Pricing\Domain\ValueObjects\PriceResult;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;

/**
 * Example per-customer override: applies a 10% discount on top of the
 * default calculator output for the "flyer" product slug.
 *
 * This is wired by Custom\customer\Providers\CustomerServiceProvider.
 */
class FlyerPriceCalculator implements PriceCalculatorInterface
{
    public function __construct(private readonly DefaultPriceCalculator $base)
    {
    }

    public function calculate(ProductConfiguration $configuration): PriceResult
    {
        $base = $this->base->calculate($configuration);
        if (! $base->valid) {
            return $base;
        }

        // Reuse the VAT rate the base calculator already applied — keeps EU
        // country-specific rates honoured even after the discount.
        $rate = $base->netPrice > 0 ? $base->tax / $base->netPrice : 0;

        $discount = $base->netPrice * 0.10;
        $newNet = $base->netPrice - $discount;
        $newTax = $newNet * $rate;
        $newGross = $newNet + $newTax;

        return new PriceResult(
            valid: true,
            currency: $base->currency,
            netPrice: $newNet,
            tax: $newTax,
            grossPrice: $newGross,
            breakdown: [
                ...$base->breakdown,
                ['label' => 'Loyalty discount (10%)', 'amount' => -$discount],
            ],
            note: 'Flyer loyalty discount applied.',
        );
    }
}
