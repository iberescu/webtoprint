<?php

use Modules\PIM\Domain\Models\Product;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;

it('exposes immutable fields', function () {
    $p = new Product(['name' => 'Flyer', 'slug' => 'flyer']);
    $cfg = new ProductConfiguration(
        product: $p,
        configuration: ['format' => 'a4', 'paper' => '125g'],
        quantity: 100,
        currency: 'EUR',
    );

    expect($cfg->product)->toBe($p);
    expect($cfg->configuration)->toBe(['format' => 'a4', 'paper' => '125g']);
    expect($cfg->quantity)->toBe(100);
    expect($cfg->currency)->toBe('EUR');
});

it('reads a configuration value with fallback', function () {
    $cfg = new ProductConfiguration(
        product: new Product(),
        configuration: ['paper' => '170g'],
        quantity: 1,
    );

    expect($cfg->get('paper'))->toBe('170g');
    expect($cfg->get('missing'))->toBeNull();
    expect($cfg->get('missing', 'fallback'))->toBe('fallback');
});
