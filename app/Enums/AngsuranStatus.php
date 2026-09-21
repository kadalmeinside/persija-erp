<?php

namespace App\Enums;

enum AngsuranStatus: string
{
    case PENDING = 'Pending';
    case PAID    = 'Paid';
    case SKIPPED = 'Skipped'; // untuk bulan yang di-skip (misal: cuti panjang)

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Belum Dibayar',
            self::PAID    => 'Sudah Dibayar',
            self::SKIPPED => 'Dilewati',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID    => 'green',
            self::SKIPPED => 'gray',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
