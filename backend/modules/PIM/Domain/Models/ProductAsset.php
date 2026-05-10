<?php

namespace Modules\PIM\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;
use Modules\FileStorage\Domain\Models\File;

class ProductAsset extends Model
{
    use HasUuid;

    protected $table = 'product_assets';

    protected $fillable = ['product_id', 'file_id', 'role', 'sort_order'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
