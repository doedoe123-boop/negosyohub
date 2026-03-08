<?php

namespace App;

enum PayoutMethod: string
{
    case BankTransfer = 'bank_transfer';
    case GCash = 'gcash';
    case Maya = 'maya';

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank Transfer',
            self::GCash => 'GCash',
            self::Maya => 'Maya',
        };
    }
}
