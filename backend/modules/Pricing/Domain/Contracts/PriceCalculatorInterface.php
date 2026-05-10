<?php

namespace Modules\Pricing\Domain\Contracts;

use Modules\Pricing\Domain\ValueObjects\PriceResult;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;

/**
 * Per-spec §7. Custom calculators implement this and are bound by SKU/slug
 * via CalculatorRegistry. The DefaultPriceCalculator is the fallback.
 */
interface PriceCalculatorInterface
{
    public function calculate(ProductConfiguration $configuration): PriceResult;
}
