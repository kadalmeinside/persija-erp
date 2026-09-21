<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case TRANSFER = 'Transfer';
    case CASH = 'Cash';

    public function label(): string
    {
        return match($this) {
            self::TRANSFER => 'Transfer Bank',
            self::CASH => 'Tunai / Kas Kecil',
        };
    }
}
