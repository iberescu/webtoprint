<?php

namespace Modules\PIM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PIM\Domain\Models\ProductOption;
use Modules\PIM\Domain\Models\ProductOptionValue;

class ProductOptionValueController extends Controller
{
    public function index(ProductOption $option): JsonResponse
    {
        return response()->json(['data' => $option->values]);
    }

    public function store(Request $request, ProductOption $option): JsonResponse
    {
        $value = $option->values()->create($this->validated($request));
        return response()->json($value, 201);
    }

    public function show(ProductOptionValue $value): JsonResponse
    {
        return response()->json($value);
    }

    public function update(Request $request, ProductOptionValue $value): JsonResponse
    {
        $value->update($this->validated($request));
        return response()->json($value);
    }

    public function destroy(ProductOptionValue $value): JsonResponse
    {
        $value->delete();
        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:64',
            'label' => 'required|string|max:255',
            'value' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'metadata_json' => 'nullable|array',
        ]);
    }
}
