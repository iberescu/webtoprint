<?php

use Modules\PIM\Domain\Models\Product;
use Modules\Pricing\Domain\Models\PriceModifier;
use Modules\Pricing\Domain\Models\PriceRule;
use Modules\Pricing\Domain\Models\PriceTable;
use Modules\Pricing\Domain\Services\DefaultPriceCalculator;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;

/**
 * Exhaustive checks for the default price calculator. We hand-build a small
 * price table and verify every branch: row matching, quantity breaks, setup
 * fees, modifiers, min_price, tax.
 */
function priceFixture(array $rows, array $modifiers = [], array $rules = []): Product
{
    $product = Product::query()->create([
        'name' => 'Flyer', 'slug' => 'fixture-' . uniqid(), 'status' => 'published',
    ]);

    $table = PriceTable::query()->create([
        'product_id' => $product->id,
        'axes_json' => [['option' => 'format'], ['option' => 'paper']],
        'name' => 'default',
    ]);
    foreach ($rows as $r) {
        $table->rows()->create(['match_json' => $r['match'], 'quantity_breaks_json' => $r['breaks']]);
    }
    foreach ($modifiers as $m) {
        PriceModifier::query()->create(['product_id' => $product->id] + $m);
    }
    foreach ($rules as $r) {
        PriceRule::query()->create(['product_id' => $product->id] + $r);
    }

    return $product;
}

function calc(Product $product, array $config, int $qty): array
{
    return (new DefaultPriceCalculator())->calculate(
        new ProductConfiguration($product, $config, $qty)
    )->toArray();
}

it('multiplies unit price by quantity, adds setup fee, applies tax', function () {
    $product = priceFixture([
        ['match' => ['format' => 'a4'], 'breaks' => [
            ['min_qty' => 100, 'unit_price' => 0.30, 'setup_fee' => 15],
        ]],
    ]);

    $r = calc($product, ['format' => 'a4', 'paper' => '125g'], 100);
    expect($r['valid'])->toBeTrue();
    expect($r['net_price'])->toBe(45.0);   // 0.30 * 100 + 15
    expect($r['tax'])->toBe(8.55);          // 19% of 45
    expect($r['gross_price'])->toBe(53.55);
    expect($r['breakdown'])->toHaveCount(2);
});

it('selects the largest quantity break <= requested quantity', function () {
    $product = priceFixture([
        ['match' => ['format' => 'a4'], 'breaks' => [
            ['min_qty' => 100, 'unit_price' => 0.30],
            ['min_qty' => 250, 'unit_price' => 0.18],
            ['min_qty' => 500, 'unit_price' => 0.12],
        ]],
    ]);

    expect(calc($product, ['format' => 'a4'], 200)['net_price'])->toBe(60.0);  // 0.30 × 200
    expect(calc($product, ['format' => 'a4'], 300)['net_price'])->toBe(54.0);  // 0.18 × 300
    expect(calc($product, ['format' => 'a4'], 600)['net_price'])->toBe(72.0);  // 0.12 × 600
});

it('prefers the most specific row (more match keys wins)', function () {
    $product = priceFixture([
        ['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 1, 'unit_price' => 1.00]]],
        ['match' => ['format' => 'a4', 'paper' => '170g'], 'breaks' => [['min_qty' => 1, 'unit_price' => 2.00]]],
    ]);

    expect(calc($product, ['format' => 'a4', 'paper' => '125g'], 1)['net_price'])->toBe(1.0);
    expect(calc($product, ['format' => 'a4', 'paper' => '170g'], 1)['net_price'])->toBe(2.0);
});

it('applies a per_unit modifier scaled to quantity', function () {
    $product = priceFixture(
        rows: [['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 1, 'unit_price' => 0.30]]]],
        modifiers: [[
            'label' => 'Lamination',
            'match_json' => ['refinement' => 'lamination'],
            'strategy' => 'per_unit',
            'amount' => 0.05,
        ]],
    );

    $r = calc($product, ['format' => 'a4', 'paper' => '125g', 'refinement' => 'lamination'], 100);
    // base 30 + lamination 5 = 35 net
    expect($r['net_price'])->toBe(35.0);
    expect($r['breakdown'])->toHaveCount(2);
});

it('skips modifiers that do not match the configuration', function () {
    $product = priceFixture(
        rows: [['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 1, 'unit_price' => 1.0]]]],
        modifiers: [[
            'label' => 'Lamination',
            'match_json' => ['refinement' => 'lamination'],
            'strategy' => 'flat',
            'amount' => 50,
        ]],
    );

    $r = calc($product, ['format' => 'a4', 'refinement' => 'none'], 1);
    expect($r['net_price'])->toBe(1.0);
});

it('applies a percent modifier on the running breakdown', function () {
    $product = priceFixture(
        rows: [['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 1, 'unit_price' => 100.0]]]],
        modifiers: [[
            'label' => 'Rush',
            'match_json' => ['delivery' => 'next_day'],
            'strategy' => 'percent',
            'amount' => 25, // 25%
        ]],
    );

    $r = calc($product, ['format' => 'a4', 'delivery' => 'next_day'], 1);
    expect($r['net_price'])->toBe(125.0);
});

it('enforces min_price when net falls below threshold', function () {
    $product = priceFixture(
        rows: [['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 1, 'unit_price' => 1.0]]]],
        rules: [['kind' => 'min_price', 'rule_json' => ['amount' => 25]]],
    );

    $r = calc($product, ['format' => 'a4'], 1);
    expect($r['net_price'])->toBe(25.0);
    expect($r['breakdown'])->toContain(['label' => 'Minimum price adjustment', 'amount' => 24.0]);
});

it('returns invalid when no price table exists for the product', function () {
    $product = Product::query()->create(['name' => 'X', 'slug' => 'x-' . uniqid(), 'status' => 'published']);
    $r = calc($product, ['format' => 'a4'], 1);
    expect($r['valid'])->toBeFalse();
    expect($r['errors'][0]['code'])->toBe('price_invalid');
});

it('returns invalid when the configuration matches no row', function () {
    $product = priceFixture([
        ['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 1, 'unit_price' => 1.0]]],
    ]);

    $r = calc($product, ['format' => 'a99'], 1);
    expect($r['valid'])->toBeFalse();
});

it('returns invalid when no quantity break covers the requested qty', function () {
    $product = priceFixture([
        ['match' => ['format' => 'a4'], 'breaks' => [['min_qty' => 100, 'unit_price' => 0.30]]],
    ]);

    $r = calc($product, ['format' => 'a4'], 50);
    expect($r['valid'])->toBeFalse();
});
