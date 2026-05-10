<?php

namespace Modules\PIM\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Concerns\HasUuid;

class ProductOption extends Model
{
    use HasUuid;

    protected $table = 'product_options';

    protected $fillable = [
        'product_id', 'code', 'label', 'type', 'required',
        'sort_order', 'help_text', 'config_json',
    ];

    protected $casts = [
        'required' => 'bool',
        'sort_order' => 'int',
        'config_json' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order');
    }
}
