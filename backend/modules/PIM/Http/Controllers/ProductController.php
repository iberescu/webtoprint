<?php

namespace Modules\PIM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\PIM\Application\Actions\SnapshotProduct;
use Modules\PIM\Domain\Models\Product;

class ProductController extends Controller
{
    public function __construct(private readonly SnapshotProduct $snapshot)
    {
    }

    /** Public: list published products. */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->where('status', 'published')->with('category', 'assets.file');

        if ($category = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $category));
        }

        return response()->json([
            'data' => $query->orderBy('sort_order')->paginate((int) $request->query('per_page', 24)),
        ]);
    }

    /** Public: show one published product by slug. */
    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with('category', 'assets.file')
            ->firstOrFail();

        return response()->json($product);
    }

    /** Admin list (any status). */
    public function adminIndex(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Product::query()->with('category')->paginate((int) $request->query('per_page', 50)),
        ]);
    }

    public function adminShow(Product $product): JsonResponse
    {
        return response()->json($product->load('category', 'options.values', 'rules', 'assets.file'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] ??= Str::slug($data['name']);

        $product = Product::query()->create($data);
        $this->snapshot->execute($product);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $product->update($this->validated($request, $product->id));
        $this->snapshot->execute($product);
        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'category_id' => 'nullable|uuid|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug' . ($ignoreId ? ",$ignoreId" : ''),
            'sku' => 'nullable|string|max:128',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,published,archived',
            'requires_design' => 'nullable|boolean',
            'allows_pdf_upload' => 'nullable|boolean',
            'default_bleed_mm' => 'nullable|integer|min:0|max:50',
            'default_safe_margin_mm' => 'nullable|integer|min:0|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'metadata_json' => 'nullable|array',
        ]);
    }
}
