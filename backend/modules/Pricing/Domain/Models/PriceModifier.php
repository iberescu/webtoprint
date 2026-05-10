<?php

namespace Modules\Pricing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Concerns\HasUuid;

class PriceModifier extends Model
{
    use HasUuid;

    protected $table = 'price_modifiers';
    protected $fillable = ['product_id', 'label', 'match_json', 'strategy', 'amount'];
    protected $casts = ['match_json' => 'array', 'amount' => 'float'];
}
