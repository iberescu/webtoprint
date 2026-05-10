<?php

namespace Modules\PIM\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;

class ProductVersion extends Model
{
    use HasUuid;

    protected $table = 'product_versions';

    protected $fillable = ['product_id', 'version', 'snapshot_json'];

    protected $casts = ['snapshot_json' => 'array', 'version' => 'int'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
