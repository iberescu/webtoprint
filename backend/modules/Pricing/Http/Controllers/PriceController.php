<?php

namespace Modules\Pricing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Rules\RuleEvaluator;
use Modules\Pricing\Domain\Services\CalculatorRegistry;
use Modules\Pricing\Domain\ValueObjects\ProductConfiguration;

class PriceController extends Controller
{
    public function __construct(
        private readonly CalculatorRegistry $calculators,
        private readonly RuleEvaluator $ruleEvaluator,
    ) {
    }

    public function calculate(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'configuration' => 'required|array',
            'currency' => 'nullable|string|size:3',
        ]);

        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with('options.values', 'rules')
            ->firstOrFail();

        // Reject the calculation upfront if rules already say the combo is invalid.
        $rules = $this->ruleEvaluator->evaluate($product, $data['configuration']);
        if (! $rules['valid']) {
            return response()->json([
                'valid' => false,
                'errors' => $rules['errors'],
            ], 422);
        }

        $quantity = (int) ($data['configuration']['quantity'] ?? 1);
        $config = new ProductConfiguration(
            product: $product,
            configuration: $data['configuration'],
            quantity: $quantity,
            currency: strtoupper($data['currency'] ?? 'EUR'),
        );

        $result = $this->calculators->for($product)->calculate($config);

        return response()->json($result->toArray(), $result->valid ? 200 : 422);
    }
}
