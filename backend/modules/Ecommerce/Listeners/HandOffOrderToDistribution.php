<?php

namespace Modules\Ecommerce\Listeners;

use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Contracts\ProductionJobInput;
use Modules\Ecommerce\Events\OrderPlaced;
use Modules\PIM\Application\Actions\SnapshotProduct;
use Modules\PIM\Domain\Models\Product;

/**
 * Adapter — owned by the Ecommerce module so that Distribution stays unaware
 * of Vanilo (or any other store).
 *
 * On OrderPlaced, walk Vanilo's order items and produce one
 * Distribution\ProductionJobInput per line. PIM is asked for a fresh product
 * snapshot at hand-off time so Distribution sees the configuration that was
 * sold, not whatever the catalogue looks like later.
 */
class HandOffOrderToDistribution
{
    public function __construct(
        private readonly CreateProductionJob $create,
        private readonly SnapshotProduct $snapshotProduct,
    ) {
    }

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        // Customer details for the jobsheet — fetched directly to avoid
        // Vanilo's UserProxy (which depends on Konekt's User module).
        $customer = $order->user_id
            ? \Modules\Ecommerce\Domain\Models\Customer::query()->find($order->user_id)
            : null;
        $customerSnapshot = [
            'name' => optional($customer)->name,
            'email' => optional($customer)->email,
            'phone' => optional($customer)->phone,
        ];

        foreach ($order->items as $item) {
            $config = $item->configuration ?? [];

            // Vanilo CartItem.configuration was populated by CartController::addItem.
            $designId = $config['design_id'] ?? null;
            $configuration = $config['configuration'] ?? [];

            // The product on a cart item is a morph; we expect Modules\PIM\Domain\Models\Product.
            $product = $item->product;
            if (! $product instanceof Product) {
                continue; // not one of ours, skip
            }
            $snapshot = $this->snapshotProduct->execute($product);

            $artworkFileId = $designId
                ? optional(\Modules\Designer\Domain\Models\Design::query()->find($designId))->print_pdf_file_id
                : null;

            $this->create->execute(new ProductionJobInput(
                externalOrderRef: (string) $order->getNumber(),
                externalOrderItemRef: (string) $item->id,
                source: 'vanilo',
                productName: $product->name,
                productSnapshot: $snapshot->snapshot_json,
                configuration: $configuration,
                quantity: (int) $item->quantity,
                artworkFileId: $artworkFileId,
                customerSnapshot: array_filter($customerSnapshot),
                shippingAddress: (array) $order->shipping_address ?? [],
                billingAddress: (array) $order->billing_address ?? [],
                metadata: ['line_total' => $item->total ?? null],
            ));
        }
    }
}
