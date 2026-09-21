<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'Draft';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }
}
