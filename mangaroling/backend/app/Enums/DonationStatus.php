<?php

namespace App\Enums;

enum DonationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
