<?php

namespace Modules\Core\Domain\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Marker trait we use across modules to standardise on UUID primary keys.
 * Aliases Eloquent's HasUuids so we can swap implementations centrally later.
 */
trait HasUuid
{
    use HasUuids;
}
