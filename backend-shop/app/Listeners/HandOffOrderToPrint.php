<?php

namespace Shop\Listeners;

use Shop\Events\OrderPlaced;
use Shop\Models\Customer;
use Shop\Models\Product;
use Shop\Services\PrintApi;

/**
 * On `OrderPlaced` (fired by `PlaceOrder` after the Vanilo order is created),
 * walk the line items, fetch a fresh product snapshot from print for each,
 * and POST a batch of production-job inputs to print's internal API.
 *
 * Replaces the previous in-process listener (HandOffOrderToDistribution) —
 * the wire format is identical, just over HTTP now.
 */
class HandOffOrderToPrint
{
    public function __construct(private readonly PrintApi $print)
    {
    }

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        $customer = $order->user_id ? Customer::query()->find($order->user_id) : null;
        $customerSnapshot = array_filter([
            'name' => optional($customer)->name,
            'email' => optional($customer)->email,
            'phone' => optional($customer)->phone,
        ]);

        $jobs = [];

        foreach ($order->items as $item) {
            $config = $item->configuration ?? [];
            $designId = $config['design_id'] ?? null;
            $configuration = $config['configuration'] ?? [];

            $product = $item->product;
            if (!$product instanceof Product) {
                continue; // foreign morph — not ours
            }

            // Fetch a fresh snapshot from print at hand-off time so we
            // record the configuration as it was sold, not whatever the
            // catalogue looks like later.
            $fresh = $this->print->resolveProduct($product->id, withSnapshot: true);
            $snapshot = data_get($fresh->metadata_json, 'snapshot', []);

            $artworkFileId = null;
            if ($designId) {
                $info = $this->print->designPreflight($designId);
                $artworkFileId = $info['print_pdf_file_id'] ?? null;
            }

            $jobs[] = [
                'external_order_ref' => (string) $order->getNumber(),
                'external_order_item_ref' => (string) $item->id,
                'source' => 'shop-vanilo',
                'product_name' => $product->name,
                'product_snapshot' => $snapshot,
                'configuration' => $configuration,
                'quantity' => (int) $item->quantity,
                'artwork_file_id' => $artworkFileId,
                'customer' => $customerSnapshot,
                'shipping_address' => (array) ($order->shipping_address ?? []),
                'billing_address' => (array) ($order->billing_address ?? []),
                'metadata' => ['line_total' => $item->total ?? null],
            ];
        }

        if ($jobs) {
            $this->print->createProductionJobs($jobs);
        }
    }
}
