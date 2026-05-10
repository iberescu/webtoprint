<?php

namespace Modules\Distribution\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Concerns\HasUuid;
use Modules\FileStorage\Domain\Models\File;

/**
 * Self-contained production job. Carries enough context to render a jobsheet
 * and produce JDF/MXML without ever asking the ecommerce system anything.
 *
 * Relations are limited to files (shared platform infra). External orders are
 * referenced only by string + source tag (e.g. "vanilo", "shopify").
 */
class ProductionJob extends Model
{
    use HasUuid;

    protected $table = 'production_jobs';

    protected $fillable = [
        'source', 'external_order_ref', 'external_order_item_ref',
        'job_number', 'product_name',
        'configuration_snapshot_json', 'artwork_file_id', 'status',
        'package_file_id', 'jobsheet_file_id', 'jdf_file_id', 'mxml_file_id',
        'metadata_json',
    ];

    protected $casts = [
        'configuration_snapshot_json' => 'array',
        'metadata_json' => 'array',
    ];

    public function artwork(): BelongsTo { return $this->belongsTo(File::class, 'artwork_file_id'); }
    public function package(): BelongsTo { return $this->belongsTo(File::class, 'package_file_id'); }
    public function jobsheet(): BelongsTo { return $this->belongsTo(File::class, 'jobsheet_file_id'); }
    public function jdf(): BelongsTo { return $this->belongsTo(File::class, 'jdf_file_id'); }
    public function mxml(): BelongsTo { return $this->belongsTo(File::class, 'mxml_file_id'); }
}
