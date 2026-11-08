<?php

namespace App\Enums;

enum PlaybackEventType: string
{
    case Play = 'play';
    case Pause = 'pause';
    case Replay = 'replay';
    case Complete = 'complete';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
