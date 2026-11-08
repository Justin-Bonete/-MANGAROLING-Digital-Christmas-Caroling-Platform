<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $desc = [
            RoleName::SystemAdministrator->value => 'Manages system settings, users, and audit logs.',
            RoleName::CampaignManager->value => 'Creates campaigns, recipients, invitations, and playlists.',
            RoleName::FinanceOfficer->value => 'Reviews, verifies, and rejects donations; financial reports.',
        ];
        foreach ($desc as $name => $d) {
            Role::updateOrCreate(['name' => $name], ['description' => $d]);
        }
    }
}
