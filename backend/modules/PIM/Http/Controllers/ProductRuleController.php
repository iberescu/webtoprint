<?php

namespace Modules\PIM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Models\ProductRule;

class ProductRuleController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json(['data' => $product->rules]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $rule = $product->rules()->create($this->validated($request));
        return response()->json($rule, 201);
    }

    public function show(ProductRule $rule): JsonResponse
    {
        return response()->json($rule);
    }

    public function update(Request $request, ProductRule $rule): JsonResponse
    {
        $rule->update($this->validated($request));
        return response()->json($rule);
    }

    public function destroy(ProductRule $rule): JsonResponse
    {
        $rule->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'kind' => 'required|in:whitelist,blacklist',
            'rule_json' => 'required|array',
            'reason' => 'nullable|string|max:512',
            'priority' => 'nullable|integer|min:0',
        ]);
    }
}
