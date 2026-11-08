<?php

namespace App\Enums;

/** Fine-grained abilities. Roles map to these in App\Support\RolePermissions. */
enum Permission: string
{
    // System administrator
    case UsersManage = 'users.manage';
    case SettingsManage = 'settings.manage';
    case OrganizationsManage = 'organizations.manage';
    case AuditLogsView = 'audit_logs.view';
    case SystemMonitor = 'system.monitor';

    // Campaign manager
    case CampaignsManage = 'campaigns.manage';
    case RecipientsManage = 'recipients.manage';
    case InvitationsManage = 'invitations.manage';
    case MusicManage = 'music.manage';
    case ReportsCampaigns = 'reports.campaigns';

    // Finance officer
    case DonationsView = 'donations.view';
    case DonationsReview = 'donations.review';
    case ProofsView = 'proofs.view';
    case ReportsFinancial = 'reports.financial';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
