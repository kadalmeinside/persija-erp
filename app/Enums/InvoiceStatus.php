<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'Draft';
    case Unpaid = 'Unpaid';
    case Partial = 'Partial';
    case Paid = 'Paid';
    case Cancelled = 'Cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
            self::Paid => 'Lunas',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft => 'gray',
            self::Unpaid => 'red',
            self::Partial => 'yellow',
            self::Paid => 'green',
            self::Cancelled => 'gray',
        };
    }
}
