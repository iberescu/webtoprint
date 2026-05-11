<?php

namespace Modules\Ecommerce\Domain\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Customer is our authenticatable user for the storefront. Vanilo's order
 * model points back here via config('vanilo.user.model').
 *
 * Uses bigint id (not UUID) to satisfy Vanilo / Konekt schema expectations.
 */
class Customer extends Authenticatable
{
    use HasApiTokens;

    // We use the standard `users` table so Vanilo / Konekt migrations can
    // extend it (type, is_active, soft-deletes) without us forking their schema.
    protected $table = 'users';
    protected $fillable = ['email', 'name', 'password', 'phone', 'metadata_json'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['metadata_json' => 'array', 'password' => 'hashed'];

    public function orders()
    {
        $orderClass = config('vanilo.foundation.models.order', \Vanilo\Order\Models\Order::class);
        return $this->hasMany($orderClass, 'user_id');
    }
}
