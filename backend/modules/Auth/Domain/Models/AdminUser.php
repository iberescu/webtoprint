<?php

namespace Modules\Auth\Domain\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Core\Domain\Concerns\HasUuid;

class AdminUser extends Authenticatable
{
    use HasApiTokens;
    use HasUuid;
    use Notifiable;

    protected $table = 'admin_users';

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
