<?php

namespace Modules\Pricing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Concerns\HasUuid;

class PriceRule extends Model
{
    use HasUuid;

    protected $table = 'price_rules';
    protected $fillable = ['product_id', 'kind', 'rule_json'];
    protected $casts = ['rule_json' => 'array'];
}
