<?php

namespace Modules\InternalBridge\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\PIM\Application\Actions\SnapshotProduct;
use Modules\PIM\Domain\Models\Product;

/**
 * Read-only product endpoint for sibling services (the shop). Returns the
 * minimum data the shop needs to:
 *   - validate the product_id passed by the storefront on cart-add
 *   - cache product name/slug for Vanilo's morph relation
 *   - retrieve a frozen snapshot when handing the order to Distribution
 */
class InternalProductController extends Controller
{
    public function __construct(private readonly SnapshotProduct $snapshot)
    {
    }

    public function show(string $id, ?string $with = null): JsonResponse
    {
        $product = Product::query()->findOrFail($id);

        $payload = [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'sku' => $product->sku,
            'status' => $product->status,
            'requires_design' => $product->requires_design,
            'metadata_json' => $product->metadata_json,
        ];

        if (request()->boolean('snapshot')) {
            $payload['snapshot'] = $this->snapshot->execute($product)->snapshot_json;
        }

        return response()->json($payload);
    }
}
