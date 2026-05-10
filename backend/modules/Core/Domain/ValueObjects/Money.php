<?php

namespace Modules\Core\Domain\ValueObjects;

/**
 * Tiny immutable money value object — amount stored in minor units (cents).
 * Pricing engine, breakdowns and tax all share this representation.
 */
final class Money
{
    public function __construct(
        public readonly int $amountMinor,
        public readonly string $currency,
    ) {
    }

    public static function fromMajor(float|int|string $major, string $currency): self
    {
        return new self((int) round(((float) $major) * 100), strtoupper($currency));
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amountMinor - $other->amountMinor, $this->currency);
    }

    public function times(float $factor): self
    {
        return new self((int) round($this->amountMinor * $factor), $this->currency);
    }

    public function major(): float
    {
        return $this->amountMinor / 100;
    }

    public function toArray(): array
    {
        return ['amount' => $this->major(), 'currency' => $this->currency];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($other->currency !== $this->currency) {
            throw new \InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
    }
}
