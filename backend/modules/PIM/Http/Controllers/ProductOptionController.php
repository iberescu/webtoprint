<?php

namespace Modules\PIM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Models\ProductOption;

class ProductOptionController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        return response()->json(['data' => $product->options()->with('values')->get()]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $this->validated($request);
        $option = $product->options()->create($data);
        return response()->json($option, 201);
    }

    public function show(ProductOption $option): JsonResponse
    {
        return response()->json($option->load('values'));
    }

    public function update(Request $request, ProductOption $option): JsonResponse
    {
        $option->update($this->validated($request));
        return response()->json($option);
    }

    public function destroy(ProductOption $option): JsonResponse
    {
        $option->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:64',
            'label' => 'required|string|max:255',
            'type' => 'required|in:select,radio,checkbox,number,range,text,boolean',
            'required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'help_text' => 'nullable|string',
            'config_json' => 'nullable|array',
        ]);
    }
}
