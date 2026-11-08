<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case Gcash = 'gcash';
    case BankTransfer = 'bank_transfer';

    /** @return string[] */
    public static function values(): array { return array_column(self::cases(), 'value'); }
}
