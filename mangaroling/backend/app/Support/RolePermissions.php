<?php

namespace App\Support;

use App\Enums\Permission as P;
use App\Enums\RoleName;

final class RolePermissions
{
    /** @return array<string, list<P>> */
    public static function map(): array
    {
        return [
            RoleName::SystemAdministrator->value => [
                P::UsersManage, P::SettingsManage, P::OrganizationsManage, P::AuditLogsView, P::SystemMonitor,
            ],
            RoleName::CampaignManager->value => [
                P::CampaignsManage, P::RecipientsManage, P::InvitationsManage, P::MusicManage, P::ReportsCampaigns,
            ],
            RoleName::FinanceOfficer->value => [
                P::DonationsView, P::DonationsReview, P::ProofsView, P::ReportsFinancial,
            ],
        ];
    }

    /** Union of permissions for the given role names, sorted. @return string[] */
    public static function forRoles(array $roleNames): array
    {
        $granted = [];
        foreach ($roleNames as $name) {
            foreach (self::map()[$name] ?? [] as $permission) {
                $granted[$permission->value] = true;
            }
        }
        $keys = array_keys($granted);
        sort($keys);

        return $keys;
    }
}
