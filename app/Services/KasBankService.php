<?php

namespace App\Services;

use App\Models\JurnalDetail;
use App\Models\KasBank;

class KasBankService
{
    /**
     * Single source of truth for real-time GL balance of a KasBank account.
     * Balance = saldo_awal + SUM(Posted debit) - SUM(Posted kredit).
     */
    public function getSaldoGl(KasBank $kasBank): float
    {
        $saldoAwal = (float) ($kasBank->saldo_awal ?? 0);

        if (!$kasBank->id_akun_gl) {
            return $saldoAwal;
        }

        $movements = JurnalDetail::where('id_akun', $kasBank->id_akun_gl)
            ->whereHas('header', fn ($q) => $q->where('status', 'Posted'))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        return $saldoAwal + (float) ($movements->total_debit ?? 0) - (float) ($movements->total_kredit ?? 0);
    }
}
