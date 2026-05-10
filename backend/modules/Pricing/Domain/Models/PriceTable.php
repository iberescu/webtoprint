<?php

namespace Modules\Pricing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Concerns\HasUuid;
use Modules\PIM\Domain\Models\Product;

class PriceTable extends Model
{
    use HasUuid;

    protected $table = 'price_tables';
    protected $fillable = ['product_id', 'price_list_id', 'name', 'axes_json'];
    protected $casts = ['axes_json' => 'array'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PriceTableRow::class);
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }
}
