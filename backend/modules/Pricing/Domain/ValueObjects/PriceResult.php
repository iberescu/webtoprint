<?php

namespace Modules\Pricing\Domain\ValueObjects;

/**
 * Result returned from PriceCalculatorInterface::calculate().
 * Wire format mirrors spec §7.
 */
final class PriceResult
{
    /**
     * @param list<array{label:string, amount:float}> $breakdown
     */
    public function __construct(
        public readonly bool $valid,
        public readonly string $currency,
        public readonly float $netPrice,
        public readonly float $tax,
        public readonly float $grossPrice,
        public readonly array $breakdown = [],
        public readonly array $errors = [],
        public readonly ?string $note = null,
    ) {
    }

    public static function invalid(string $message, string $currency = 'EUR'): self
    {
        return new self(false, $currency, 0, 0, 0, [], [['code' => 'price_invalid', 'message' => $message]]);
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'currency' => $this->currency,
            'net_price' => round($this->netPrice, 2),
            'tax' => round($this->tax, 2),
            'gross_price' => round($this->grossPrice, 2),
            'breakdown' => array_map(fn ($l) => ['label' => $l['label'], 'amount' => round($l['amount'], 2)], $this->breakdown),
            'errors' => $this->errors,
            'note' => $this->note,
        ];
    }
}
