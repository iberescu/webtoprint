<?php

use Database\Seeders\CatalogueSeeder;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Contracts\ProductionJobInput;
use Modules\Distribution\Domain\Models\ProductionJob;
use Modules\PIM\Application\Actions\SnapshotProduct;
use Modules\PIM\Domain\Models\Product;

/**
 * Walks the spec §19 MVP completion criteria, scoped to what runs without a
 * live MinIO/Vanilo install (so it stays useful in CI). The full storefront
 * flow has its own integration test once the docker stack is up.
 */
beforeEach(function () {
    $this->seed(CatalogueSeeder::class);
});

it('exposes the configurator for a published product (criteria 6-8)', function () {
    $this->getJson('/api/v1/products/flyer/configurator')
        ->assertOk()
        ->assertJsonPath('product.slug', 'flyer')
        ->assertJsonStructure(['product', 'options', 'defaults']);
});

it('rejects blacklisted combinations (criteria 9)', function () {
    $this->postJson('/api/v1/products/flyer/validate', [
        'configuration' => ['format' => 'a4', 'paper' => '125g', 'colors' => '4-0', 'refinement' => 'none', 'quantity' => '100'],
    ])
        ->assertStatus(422)
        ->assertJsonPath('valid', false)
        ->assertJsonFragment(['code' => 'combination_not_allowed']);
});

it('calculates price for a valid configuration (criteria 10)', function () {
    $this->postJson('/api/v1/products/flyer/price', [
        'configuration' => ['format' => 'a4', 'paper' => '125g', 'colors' => '4-4', 'refinement' => 'none', 'quantity' => 100],
    ])
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonStructure(['currency', 'net_price', 'tax', 'gross_price', 'breakdown']);
});

it('creates a production job from a neutral DTO and stores it self-contained (criteria 16-21)', function () {
    /** @var Product $product */
    $product = Product::query()->where('slug', 'flyer')->firstOrFail();
    $snapshot = app(SnapshotProduct::class)->execute($product);

    $job = app(CreateProductionJob::class)->execute(new ProductionJobInput(
        externalOrderRef: 'TEST-123',
        externalOrderItemRef: '1',
        source: 'unit-test',
        productName: $product->name,
        productSnapshot: $snapshot->snapshot_json,
        configuration: ['format' => 'a4', 'paper' => '125g', 'colors' => '4-4', 'refinement' => 'none', 'quantity' => 100],
        quantity: 100,
        artworkFileId: null,
        customerSnapshot: ['name' => 'Acme Ltd', 'email' => 'orders@acme.example'],
    ));

    expect($job)->toBeInstanceOf(ProductionJob::class);
    expect($job->source)->toBe('unit-test');
    expect($job->job_number)->toBe('UNIT-TEST-TEST-123-1');

    // Self-containment: the job has zero ecommerce-side foreign keys.
    expect(ProductionJob::query()->getModel()->getFillable())
        ->not->toContain('order_id')
        ->not->toContain('order_item_id');
});
