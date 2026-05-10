<?php

use Modules\PIM\Application\Actions\SnapshotProduct;
use Modules\PIM\Domain\Models\Product;

it('persists a versioned, fully-loaded snapshot of the product', function () {
    $product = Product::query()->create([
        'name' => 'Flyer', 'slug' => 'snapshot-' . uniqid(), 'status' => 'published',
    ]);
    $opt = $product->options()->create([
        'code' => 'format', 'label' => 'Format', 'type' => 'select', 'required' => true,
    ]);
    $opt->values()->create(['code' => 'a4', 'label' => 'A4']);
    $product->rules()->create([
        'kind' => 'blacklist',
        'rule_json' => ['block' => ['colors' => '4-0']],
        'reason' => 'demo',
    ]);

    $v1 = app(SnapshotProduct::class)->execute($product);
    expect($v1->version)->toBe(1);
    expect($v1->snapshot_json['product']['name'])->toBe('Flyer');
    expect($v1->snapshot_json['options'])->toHaveCount(1);
    expect($v1->snapshot_json['options'][0]['code'])->toBe('format');
    expect($v1->snapshot_json['options'][0]['values'][0]['code'])->toBe('a4');
    expect($v1->snapshot_json['rules'])->toHaveCount(1);

    // Calling again increments the version monotonically.
    $v2 = app(SnapshotProduct::class)->execute($product);
    expect($v2->version)->toBe(2);
});
