<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Actions\GenerateProductionPackage;
use Modules\Distribution\Application\Contracts\ProductionJobInput;
use Modules\Distribution\Events\ProductionJobCreated;

function input(array $overrides = []): ProductionJobInput
{
    return new ProductionJobInput(
        externalOrderRef: $overrides['externalOrderRef'] ?? '2026-000001',
        externalOrderItemRef: $overrides['externalOrderItemRef'] ?? '7',
        source: $overrides['source'] ?? 'vanilo',
        productName: $overrides['productName'] ?? 'Flyer',
        productSnapshot: $overrides['productSnapshot'] ?? ['product' => ['name' => 'Flyer']],
        configuration: $overrides['configuration'] ?? ['format' => 'a4'],
        quantity: $overrides['quantity'] ?? 250,
        artworkFileId: $overrides['artworkFileId'] ?? null,
        customerSnapshot: $overrides['customerSnapshot'] ?? [],
        shippingAddress: $overrides['shippingAddress'] ?? [],
        billingAddress: $overrides['billingAddress'] ?? [],
        metadata: $overrides['metadata'] ?? [],
    );
}

it('builds a stable, deterministic job_number from source + refs', function () {
    Event::fake();
    Bus::fake();

    $job = app(CreateProductionJob::class)->execute(input());

    expect($job->job_number)->toBe('VANILO-2026-000001-7');
    expect($job->source)->toBe('vanilo');
    expect($job->external_order_ref)->toBe('2026-000001');
    expect($job->external_order_item_ref)->toBe('7');
});

it('packs the inbound DTO into configuration_snapshot_json', function () {
    Event::fake(); Bus::fake();

    $job = app(CreateProductionJob::class)->execute(input([
        'configuration' => ['format' => 'a4', 'paper' => '125g', 'quantity' => '250'],
        'customerSnapshot' => ['name' => 'Acme'],
        'shippingAddress' => ['city' => 'Leeds'],
    ]));

    $snap = $job->configuration_snapshot_json;
    expect($snap['configuration'])->toBe(['format' => 'a4', 'paper' => '125g', 'quantity' => '250']);
    expect($snap['customer'])->toBe(['name' => 'Acme']);
    expect($snap['shipping_address'])->toBe(['city' => 'Leeds']);
    expect($snap['quantity'])->toBe(250);
});

it('dispatches GenerateProductionPackage by default', function () {
    Event::fake();
    Bus::fake();

    $job = app(CreateProductionJob::class)->execute(input());

    Bus::assertDispatched(GenerateProductionPackage::class, function ($queued) use ($job) {
        return $queued->productionJobId === $job->id;
    });
});

it('fires ProductionJobCreated event', function () {
    Event::fake();
    Bus::fake();

    $job = app(CreateProductionJob::class)->execute(input());

    Event::assertDispatched(ProductionJobCreated::class, fn ($e) => $e->job->id === $job->id);
});

it('skips package dispatch when distribution.auto_generate is false', function () {
    Event::fake(); Bus::fake();
    config(['distribution.auto_generate' => false]);

    app(CreateProductionJob::class)->execute(input());
    Bus::assertNotDispatched(GenerateProductionPackage::class);
});
