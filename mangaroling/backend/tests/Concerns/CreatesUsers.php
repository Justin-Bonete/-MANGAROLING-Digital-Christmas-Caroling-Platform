<?php

namespace Tests\Concerns;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;

trait CreatesUsers
{
    public const PASSWORD = 'Str0ng-Passw0rd!x';

    protected Organization $org;

    protected function setUpUsers(): void
    {
        $this->seed(RoleSeeder::class);
        $this->org = Organization::factory()->create();
    }

    /** @param RoleName|RoleName[] $roles */
    protected function makeUser(RoleName|array $roles = [], array $attributes = [], ?Organization $org = null): User
    {
        $user = User::factory()->create($attributes + [
            'organization_id' => ($org ?? $this->org)->id,
            'password' => self::PASSWORD,
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $names = array_map(fn (RoleName $r) => $r->value, is_array($roles) ? $roles : [$roles]);
        $user->roles()->sync(Role::whereIn('name', $names)->pluck('id'));

        return $user->load('roles');
    }

    /** Headers that make Sanctum treat the request as coming from the SPA (cookie session mode). */
    protected function spa(): array
    {
        return ['Referer' => 'http://localhost:5173', 'Origin' => 'http://localhost:5173'];
    }
}
