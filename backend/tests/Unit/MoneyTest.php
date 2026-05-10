<?php

use Modules\Core\Domain\ValueObjects\Money;

it('constructs from major units and stores in minor', function () {
    $m = Money::fromMajor('42.50', 'eur');
    expect($m->amountMinor)->toBe(4250);
    expect($m->currency)->toBe('EUR'); // upper-cased
    expect($m->major())->toBe(42.5);
});

it('rounds half-up when converting major→minor', function () {
    expect(Money::fromMajor(0.005, 'EUR')->amountMinor)->toBe(1);
    expect(Money::fromMajor(0.004, 'EUR')->amountMinor)->toBe(0);
});

it('adds and subtracts when currencies match', function () {
    $a = Money::fromMajor(10, 'EUR');
    $b = Money::fromMajor(2.5, 'EUR');
    expect($a->plus($b)->major())->toBe(12.5);
    expect($a->minus($b)->major())->toBe(7.5);
});

it('throws on currency mismatch', function () {
    Money::fromMajor(10, 'EUR')->plus(Money::fromMajor(5, 'USD'));
})->throws(InvalidArgumentException::class, 'Currency mismatch');

it('multiplies with a scalar factor and rounds the result', function () {
    $m = Money::fromMajor(3.33, 'EUR')->times(3);
    expect($m->major())->toBe(9.99);
});

it('serialises to a wire-shaped array', function () {
    expect(Money::fromMajor(5, 'gbp')->toArray())->toBe(['amount' => 5.0, 'currency' => 'GBP']);
});
