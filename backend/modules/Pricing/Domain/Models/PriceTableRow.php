<?php

namespace Modules\Pricing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;

class PriceTableRow extends Model
{
    use HasUuid;

    protected $table = 'price_table_rows';
    protected $fillable = ['price_table_id', 'match_json', 'quantity_breaks_json'];
    protected $casts = ['match_json' => 'array', 'quantity_breaks_json' => 'array'];

    public function table(): BelongsTo
    {
        return $this->belongsTo(PriceTable::class, 'price_table_id');
    }
}
