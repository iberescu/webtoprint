<?php

namespace Modules\Settings\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'group'];

    protected $casts = [
        'value' => 'array',
    ];
}
