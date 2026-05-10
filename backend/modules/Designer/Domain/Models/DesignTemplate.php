<?php

namespace Modules\Designer\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;
use Modules\FileStorage\Domain\Models\File;
use Modules\PIM\Domain\Models\Product;

class DesignTemplate extends Model
{
    use HasUuid;

    protected $table = 'design_templates';

    protected $fillable = [
        'product_id', 'name', 'status', 'width_mm', 'height_mm',
        'bleed_mm', 'safe_margin_mm', 'page_count',
        'thumbnail_file_id', 'template_json',
    ];

    protected $casts = [
        'width_mm' => 'int', 'height_mm' => 'int',
        'bleed_mm' => 'int', 'safe_margin_mm' => 'int', 'page_count' => 'int',
        'template_json' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(File::class, 'thumbnail_file_id');
    }
}
