<?php

namespace Modules\PIM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\PIM\Domain\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => ProductCategory::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] ??= Str::slug($data['name']);
        $category = ProductCategory::query()->create($data);
        return response()->json($category, 201);
    }

    public function show(ProductCategory $productCategory): JsonResponse
    {
        return response()->json($productCategory);
    }

    public function update(Request $request, ProductCategory $productCategory): JsonResponse
    {
        $productCategory->update($this->validated($request, $productCategory->id));
        return response()->json($productCategory);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request, ?string $ignoreId = null): array
    {
        return $request->validate([
            'parent_id' => 'nullable|uuid|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug' . ($ignoreId ? ",$ignoreId" : ''),
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }
}
