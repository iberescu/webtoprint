<?php

namespace Modules\PIM\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;

class ProductRule extends Model
{
    use HasUuid;

    protected $table = 'product_rules';

    protected $fillable = ['product_id', 'kind', 'rule_json', 'reason', 'priority'];

    protected $casts = [
        'rule_json' => 'array',
        'priority' => 'int',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
