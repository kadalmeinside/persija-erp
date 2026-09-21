<?php

namespace App\Enums;

enum PengajuanStatus: string
{
    case DRAFT = 'Draft';
    case PENDING_APPROVAL = 'Pending Approval';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
    case REVISION = 'Revision';
    case VERIFICATION = 'Verification';
    case PAID = 'Paid';
    case SETTLED = 'Settled';
    case CANCELLED = 'Cancelled';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::PENDING_APPROVAL => 'Menunggu Persetujuan',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::REVISION => 'Perlu Revisi',
            self::VERIFICATION => 'Verifikasi Finance',
            self::PAID => 'Sudah Dibayar',
            self::SETTLED => 'Selesai (Laporan Diterima)',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING_APPROVAL => 'yellow',
            self::APPROVED => 'blue',
            self::REJECTED => 'red',
            self::REVISION => 'orange',
            self::VERIFICATION => 'indigo',
            self::PAID => 'green',
            self::SETTLED => 'emerald',
            self::CANCELLED => 'red',
        };
    }
}
