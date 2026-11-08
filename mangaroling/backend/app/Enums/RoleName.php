<?php

namespace App\Enums;

enum RoleName: string
{
    case SystemAdministrator = 'system_admin';
    case CampaignManager = 'campaign_manager';
    case FinanceOfficer = 'finance_officer';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
