<?php

namespace Modules\PIM\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;

class ProductOptionValue extends Model
{
    use HasUuid;

    protected $table = 'product_option_values';

    protected $fillable = [
        'product_option_id', 'code', 'label', 'value',
        'sort_order', 'metadata_json',
    ];

    protected $casts = [
        'sort_order' => 'int',
        'metadata_json' => 'array',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
