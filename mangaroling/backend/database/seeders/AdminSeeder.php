<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL');
        $password = env('SEED_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD not set; skipping admin seed.');
            return;
        }

        $org = Organization::firstOrCreate(['name' => 'MANGAROLING Default Organization']);
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => 'System Administrator', 'password' => $password, 'organization_id' => $org->id, 'is_active' => true],
        );
        $user->roles()->syncWithoutDetaching([Role::where('name', RoleName::SystemAdministrator->value)->value('id')]);
    }
}
