<?php

namespace App\Enums;

enum ConsentStatus: string
{
    case Pending = 'pending';
    case Granted = 'granted';
    case Withdrawn = 'withdrawn';
    case OptedOut = 'opted_out';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
