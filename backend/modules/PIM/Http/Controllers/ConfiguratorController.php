<?php

namespace Modules\PIM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Rules\RuleEvaluator;

class ConfiguratorController extends Controller
{
    public function __construct(private readonly RuleEvaluator $evaluator)
    {
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->resolveProduct($slug);

        $defaults = [];
        foreach ($product->options as $option) {
            $first = $option->values->first();
            if ($first) {
                $defaults[$option->code] = $first->code;
            }
        }

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'requires_design' => $product->requires_design,
                'allows_pdf_upload' => $product->allows_pdf_upload,
            ],
            'options' => $product->options->map(fn ($o) => [
                'code' => $o->code,
                'label' => $o->label,
                'type' => $o->type,
                'required' => $o->required,
                'help_text' => $o->help_text,
                'values' => $o->values->map(fn ($v) => [
                    'code' => $v->code,
                    'label' => $v->label,
                    'value' => $v->value,
                    'metadata' => $v->metadata_json,
                ])->all(),
            ]),
            'defaults' => $defaults,
        ]);
    }

    public function validate(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'configuration' => 'required|array',
        ]);

        $product = $this->resolveProduct($slug);
        $result = $this->evaluator->evaluate($product, $data['configuration']);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    private function resolveProduct(string $slug): Product
    {
        return Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with('options.values', 'rules')
            ->firstOrFail();
    }
}
