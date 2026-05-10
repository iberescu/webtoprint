<?php

namespace Modules\FileStorage\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Concerns\HasUuid;

class File extends Model
{
    use HasUuid;

    protected $table = 'files';

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type',
        'size', 'checksum', 'visibility', 'metadata_json', 'attached',
    ];

    protected $casts = [
        'metadata_json' => 'array',
        'attached' => 'bool',
        'size' => 'int',
    ];
}
