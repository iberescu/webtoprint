<?php

namespace Modules\PIM\Application\Actions;

use Modules\PIM\Domain\Models\Product;
use Modules\PIM\Domain\Models\ProductVersion;

/**
 * Persists a snapshot of the product (with options + values + rules)
 * so that orders placed after a future config change still resolve to
 * the configuration that was live at order time.
 */
class SnapshotProduct
{
    public function execute(Product $product): ProductVersion
    {
        $product->loadMissing(['options.values', 'rules']);

        $snapshot = [
            'product' => $product->only([
                'id', 'name', 'slug', 'sku', 'description', 'status',
                'requires_design', 'allows_pdf_upload',
                'default_bleed_mm', 'default_safe_margin_mm', 'metadata_json',
            ]),
            'options' => $product->options->map(fn ($o) => [
                'code' => $o->code,
                'label' => $o->label,
                'type' => $o->type,
                'required' => (bool) $o->required,
                'config_json' => $o->config_json,
                'values' => $o->values->map(fn ($v) => [
                    'code' => $v->code,
                    'label' => $v->label,
                    'value' => $v->value,
                    'metadata_json' => $v->metadata_json,
                ])->all(),
            ])->all(),
            'rules' => $product->rules->map(fn ($r) => $r->only(['kind', 'rule_json', 'reason']))->all(),
        ];

        $next = ($product->versions()->max('version') ?? 0) + 1;

        return $product->versions()->create([
            'version' => $next,
            'snapshot_json' => $snapshot,
        ]);
    }
}
