<?php

use Modules\Pricing\Domain\ValueObjects\PriceResult;

it('serialises a valid result rounded to 2dp', function () {
    $r = new PriceResult(
        valid: true,
        currency: 'EUR',
        netPrice: 42.499,
        tax: 8.0739,
        grossPrice: 50.5729,
        breakdown: [
            ['label' => 'Base', 'amount' => 30.0049],
            ['label' => 'Surcharge', 'amount' => 12.4941],
        ],
    );

    $arr = $r->toArray();
    expect($arr['valid'])->toBeTrue();
    expect($arr['net_price'])->toBe(42.5);
    expect($arr['tax'])->toBe(8.07);
    expect($arr['gross_price'])->toBe(50.57);
    expect($arr['breakdown'])->toBe([
        ['label' => 'Base', 'amount' => 30.0],
        ['label' => 'Surcharge', 'amount' => 12.49],
    ]);
});

it('builds an invalid result via the factory', function () {
    $r = PriceResult::invalid('No row', 'GBP');
    expect($r->valid)->toBeFalse();
    expect($r->currency)->toBe('GBP');
    expect($r->errors)->toBe([['code' => 'price_invalid', 'message' => 'No row']]);
    expect($r->toArray()['gross_price'])->toBe(0.0);
});
