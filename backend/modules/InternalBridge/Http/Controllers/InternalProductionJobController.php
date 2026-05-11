<?php

namespace Modules\InternalBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Contracts\ProductionJobInput;

/**
 * Replaces the in-process `OrderPlaced → HandOffOrderToDistribution`
 * listener. Now that the shop runs in a separate Laravel app, it builds
 * the same ProductionJobInput DTOs and POSTs them here as JSON.
 */
class InternalProductionJobController extends Controller
{
    public function __construct(private readonly CreateProductionJob $create)
    {
    }

    public function batch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'jobs' => 'required|array|min:1',
            'jobs.*.external_order_ref' => 'required|string',
            'jobs.*.external_order_item_ref' => 'required|string',
            'jobs.*.source' => 'required|string',
            'jobs.*.product_name' => 'required|string',
            'jobs.*.product_snapshot' => 'required|array',
            'jobs.*.configuration' => 'required|array',
            'jobs.*.quantity' => 'required|integer|min:1',
            'jobs.*.artwork_file_id' => 'nullable|string',
            'jobs.*.customer' => 'nullable|array',
            'jobs.*.shipping_address' => 'nullable|array',
            'jobs.*.billing_address' => 'nullable|array',
            'jobs.*.metadata' => 'nullable|array',
        ]);

        $created = [];
        foreach ($data['jobs'] as $j) {
            $job = $this->create->execute(new ProductionJobInput(
                externalOrderRef: $j['external_order_ref'],
                externalOrderItemRef: $j['external_order_item_ref'],
                source: $j['source'],
                productName: $j['product_name'],
                productSnapshot: $j['product_snapshot'],
                configuration: $j['configuration'],
                quantity: (int) $j['quantity'],
                artworkFileId: $j['artwork_file_id'] ?? null,
                customerSnapshot: $j['customer'] ?? [],
                shippingAddress: $j['shipping_address'] ?? [],
                billingAddress: $j['billing_address'] ?? [],
                metadata: $j['metadata'] ?? [],
            ));
            $created[] = ['id' => $job->id, 'job_number' => $job->job_number];
        }

        return response()->json(['jobs' => $created], 201);
    }
}
