<?php

namespace Modules\Designer\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;
use Modules\FileStorage\Domain\Models\File;
use Modules\PIM\Domain\Models\Product;

class Design extends Model
{
    use HasUuid;

    protected $table = 'designs';

    protected $fillable = [
        'customer_id', 'product_id', 'template_id', 'status',
        'configuration_json', 'design_json',
        'preview_file_id', 'print_pdf_file_id',
        'approved_at', 'source',
    ];

    protected $casts = [
        'configuration_json' => 'array',
        'design_json' => 'array',
        'approved_at' => 'datetime',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function template(): BelongsTo { return $this->belongsTo(DesignTemplate::class, 'template_id'); }
    public function preview(): BelongsTo { return $this->belongsTo(File::class, 'preview_file_id'); }
    public function printPdf(): BelongsTo { return $this->belongsTo(File::class, 'print_pdf_file_id'); }
}
