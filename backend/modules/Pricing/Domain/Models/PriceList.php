<?php

namespace Modules\Pricing\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Concerns\HasUuid;

class PriceList extends Model
{
    use HasUuid;

    protected $table = 'price_lists';
    protected $fillable = ['name', 'currency', 'is_default', 'metadata_json'];
    protected $casts = ['is_default' => 'bool', 'metadata_json' => 'array'];
}
