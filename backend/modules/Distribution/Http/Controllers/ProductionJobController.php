<?php

namespace Modules\Distribution\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Distribution\Application\Actions\CreateProductionJob;
use Modules\Distribution\Application\Actions\GenerateProductionPackage;
use Modules\Distribution\Application\Contracts\ProductionJobInput;
use Modules\Distribution\Domain\Models\ProductionJob;
use Modules\FileStorage\Domain\Services\FileStorageService;

class ProductionJobController extends Controller
{
    public function __construct(private readonly CreateProductionJob $create)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => ProductionJob::query()
                ->orderByDesc('created_at')
                ->paginate((int) $request->query('per_page', 30)),
        ]);
    }

    /**
     * Manual / external API: accepts the same shape any ecommerce adapter
     * would push. No coupling to a specific cart system.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source' => 'required|string|max:32',
            'external_order_ref' => 'required|string|max:128',
            'external_order_item_ref' => 'required|string|max:128',
            'product_name' => 'required|string|max:255',
            'product_snapshot' => 'required|array',
            'configuration' => 'required|array',
            'quantity' => 'required|integer|min:1',
            'artwork_file_id' => 'nullable|uuid|exists:files,id',
            'customer' => 'nullable|array',
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $job = $this->create->execute(new ProductionJobInput(
            externalOrderRef: $data['external_order_ref'],
            externalOrderItemRef: $data['external_order_item_ref'],
            source: $data['source'],
            productName: $data['product_name'],
            productSnapshot: $data['product_snapshot'],
            configuration: $data['configuration'],
            quantity: (int) $data['quantity'],
            artworkFileId: $data['artwork_file_id'] ?? null,
            customerSnapshot: $data['customer'] ?? [],
            shippingAddress: $data['shipping_address'] ?? [],
            billingAddress: $data['billing_address'] ?? [],
            metadata: $data['metadata'] ?? [],
        ));

        return response()->json($job, 201);
    }

    public function show(ProductionJob $job): JsonResponse
    {
        return response()->json($job->load('package', 'jobsheet', 'jdf', 'mxml', 'artwork'));
    }

    public function generatePackage(ProductionJob $job): JsonResponse
    {
        GenerateProductionPackage::dispatch($job->id);
        return response()->json(['queued' => true]);
    }

    public function regenerate(ProductionJob $job): JsonResponse
    {
        GenerateProductionPackage::dispatch($job->id);
        return response()->json(['queued' => true]);
    }

    public function download(ProductionJob $job, FileStorageService $files): JsonResponse
    {
        if (! $job->package) {
            return response()->json(['message' => 'Package not yet generated.'], 409);
        }
        return response()->json(['url' => $files->signedReadUrl($job->package, 60)]);
    }
}
