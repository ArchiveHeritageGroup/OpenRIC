<?php

declare(strict_types=1);

namespace OpenRiC\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OpenRiC\Core\Traits\HasUuid;

class User extends Authenticatable
{
    use HasFactory;
    use HasUuid;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'uuid',
        'username',
        'email',
        'password',
        'display_name',
        'active',
        'locale',
        'timezone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function securityClearance(): HasOne
    {
        return $this->hasOne(UserSecurityClearance::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('permissions.name', $permissionName)
                    ->wherePivot('grant_type', 1);
            })
            ->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMINISTRATOR);
    }

    public function getIri(): string
    {
        return config('openric.user_base_uri') . '/' . $this->uuid;
    }
}
