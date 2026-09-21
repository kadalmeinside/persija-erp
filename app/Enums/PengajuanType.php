<?php

namespace App\Enums;

enum PengajuanType: string
{
    case LANGSUNG   = 'Langsung';
    case UANG_MUKA  = 'UangMuka';
    case REIMBURSE  = 'Reimburse';
    case PETTY_CASH = 'PettyCash';

    public function label(): string
    {
        return match($this) {
            self::LANGSUNG   => 'Pembayaran Langsung',
            self::UANG_MUKA  => 'Uang Muka (Cash Advance)',
            self::REIMBURSE  => 'Reimbursement',
            self::PETTY_CASH => 'Petty Cash (Kas Kecil)',
        };
    }

    /**
     * Tipe yang GL-nya langsung posting saat final-approved
     * (tidak ada step pembayaran terpisah di kemudian hari).
     */
    public function isDirectPosting(): bool
    {
        return $this === self::PETTY_CASH;
    }
}
