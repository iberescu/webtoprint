<?php

use Database\Seeders\CatalogueSeeder;
use Modules\Distribution\Domain\Models\ProductionJob;
use Modules\Ecommerce\Application\Actions\PlaceOrder;
use Modules\PIM\Domain\Models\Product;
use Vanilo\Cart\Models\Cart;

/**
 * Walks the full §13 workflow with Vanilo as the ecommerce backend:
 * configurator → validate → price → cart → checkout → OrderPlaced
 * → HandOffOrderToDistribution → ProductionJob exists.
 *
 * If this passes, swapping Vanilo for Shopify is safe — the Distribution
 * side is identical and we've proven the seam works.
 */
beforeEach(function () {
    $this->seed(CatalogueSeeder::class);
});

it('walks a full configured order from cart to production job', function () {
    // 1. configurator + override defaults to a combination that passes the
    //    blacklist (defaults give us A4 + 4/0 which the seeded rule blocks).
    $defaults = $this->getJson('/api/v1/products/flyer/configurator')->json('defaults');
    expect($defaults)->toHaveKeys(['format', 'paper', 'colors', 'refinement', 'quantity']);
    $config = array_merge($defaults, ['colors' => '4-4', 'quantity' => 250]);

    // 2. price
    $price = $this->postJson('/api/v1/products/flyer/price', [
        'configuration' => $config,
    ])->assertOk()->json();
    expect($price['valid'])->toBeTrue();
    expect($price['gross_price'])->toBeGreaterThan(0);

    // 3. add to a Vanilo cart, manually populating the configuration column the
    //    way CartController::addItem does it.
    /** @var Product $product */
    $product = Product::query()->where('slug', 'flyer')->firstOrFail();
    $cart = Cart::query()->create(['user_id' => null]);
    $product->priceOverride($price['gross_price'] / 250);
    $item = $cart->addItem($product, 250, [
        'attributes' => [
            'configuration' => [
                'design_id' => null,
                'configuration' => $config,
                'price' => $price,
            ],
        ],
    ]);
    expect($item->id)->not->toBeNull();
    expect($cart->items()->count())->toBe(1);

    // 4. place the order via PlaceOrder. This dispatches OrderPlaced, which the
    //    Ecommerce adapter listens to and translates into Distribution work.
    $order = app(PlaceOrder::class)->execute($cart->fresh('items'));
    expect($order->getNumber())->toMatch('/^\\d{4}-\\d{6}$/');

    // 5. verify the adapter handed off correctly: there should now be a
    //    ProductionJob with the spec-compliant snapshot, source=vanilo, and
    //    no foreign keys to the order or order_item.
    $jobs = ProductionJob::query()->where('source', 'vanilo')->get();
    expect($jobs)->toHaveCount(1);

    $job = $jobs->first();
    expect($job->external_order_ref)->toBe($order->getNumber());
    expect($job->product_name)->toBe('Flyer');
    expect($job->configuration_snapshot_json['quantity'])->toBe(250);
    expect($job->configuration_snapshot_json['configuration']['format'])->toBe('a4');

    // Boundary check: the production job has zero ecommerce-side columns.
    $cols = array_keys($job->getAttributes());
    expect($cols)->not->toContain('order_id')->not->toContain('order_item_id');
});

it('refuses Shopify-style production jobs that target failed-preflight artwork has no equivalent here, but verifies the DTO accepts shopify source', function () {
    // Prove that Distribution accepts the Shopify-shape DTO without
    // any code change — same action, same DTO, same downstream state.
    /** @var Product $product */
    $product = Product::query()->where('slug', 'flyer')->firstOrFail();
    $snapshot = app(\Modules\PIM\Application\Actions\SnapshotProduct::class)->execute($product);

    $job = app(\Modules\Distribution\Application\Actions\CreateProductionJob::class)->execute(
        new \Modules\Distribution\Application\Contracts\ProductionJobInput(
            externalOrderRef: '#1042',
            externalOrderItemRef: '14087351',
            source: 'shopify',
            productName: 'Flyer',
            productSnapshot: $snapshot->snapshot_json,
            configuration: ['format' => 'a4', 'paper' => '125g', 'colors' => '4-4', 'quantity' => 100],
            quantity: 100,
            artworkFileId: null,
            customerSnapshot: ['name' => 'Acme', 'email' => 'orders@acme.example'],
        )
    );

    expect($job->source)->toBe('shopify');
    expect($job->job_number)->toBe('SHOPIFY-#1042-14087351');
});
