<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Opened = 'opened';
    case Expired = 'expired';
    case Revoked = 'revoked';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
