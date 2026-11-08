<?php

namespace App\Models;

use App\Enums\Permission;
use App\Enums\RoleName;
use App\Support\RolePermissions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'organization_id', 'is_active', 'must_change_password', 'last_login_at'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /** Emails are stored lowercase so uniqueness and login are case-insensitive. */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value) => mb_strtolower(trim($value)));
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class, 'user_roles'); }

    /** @return string[] */
    public function roleNames(): array { return $this->roles->pluck('name')->values()->all(); }

    public function hasRole(RoleName|string ...$names): bool
    {
        $names = array_map(fn ($n) => $n instanceof RoleName ? $n->value : $n, $names);

        return count(array_intersect($this->roleNames(), $names)) > 0;
    }

    /** @return string[] */
    public function permissionNames(): array { return RolePermissions::forRoles($this->roleNames()); }

    public function hasPermission(Permission|string $permission): bool
    {
        $permission = $permission instanceof Permission ? $permission->value : $permission;

        return $this->is_active && in_array($permission, $this->permissionNames(), true);
    }
}
