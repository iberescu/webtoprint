<?php

namespace Modules\Distribution\Application\Actions;

use Modules\Distribution\Application\Contracts\ProductionJobInput;
use Modules\Distribution\Domain\Models\ProductionJob;
use Modules\Distribution\Events\ProductionJobCreated;

/**
 * Public entry point into the Distribution module.
 *
 * Any ecommerce adapter (Vanilo, Shopify, Magento, manual …) calls this
 * with a neutral ProductionJobInput. Distribution stays unaware of where
 * orders actually live.
 */
class CreateProductionJob
{
    public function execute(ProductionJobInput $input): ProductionJob
    {
        $job = ProductionJob::query()->create([
            'external_order_ref' => $input->externalOrderRef,
            'external_order_item_ref' => $input->externalOrderItemRef,
            'source' => $input->source,
            'job_number' => $this->buildJobNumber($input),
            'product_name' => $input->productName,
            'configuration_snapshot_json' => [
                'product' => $input->productSnapshot,
                'configuration' => $input->configuration,
                'quantity' => $input->quantity,
                'customer' => $input->customerSnapshot,
                'shipping_address' => $input->shippingAddress,
                'billing_address' => $input->billingAddress,
            ],
            'artwork_file_id' => $input->artworkFileId,
            'status' => 'pending',
            'metadata_json' => $input->metadata,
        ]);

        ProductionJobCreated::dispatch($job);

        // Default policy: kick off package generation immediately.
        // An adapter that wants a manual gate can disable this in config.
        if (config('distribution.auto_generate', true)) {
            GenerateProductionPackage::dispatch($job->id);
        }

        return $job;
    }

    private function buildJobNumber(ProductionJobInput $input): string
    {
        // {SOURCE}-{ORDER_REF}-{ITEM_REF}, kept stable so Shopify pushes are idempotent.
        return strtoupper("{$input->source}-{$input->externalOrderRef}-{$input->externalOrderItemRef}");
    }
}
