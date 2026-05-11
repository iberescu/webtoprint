<?php

namespace Shop\Models;

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
