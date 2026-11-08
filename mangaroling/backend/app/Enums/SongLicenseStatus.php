<?php

namespace App\Enums;

enum SongLicenseStatus: string
{
    case Original = 'original';
    case PublicDomain = 'public_domain';
    case Licensed = 'licensed';
    case Unlicensed = 'unlicensed';
    case Unknown = 'unknown';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }

    /** BR-006: only these license states may be played. */
    public static function eligible(): array
    {
        return [self::Original->value, self::PublicDomain->value, self::Licensed->value];
    }
}
