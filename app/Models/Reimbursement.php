<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reimbursement extends Model
{
    protected $table = 'tbl_reimbursements';

    protected $fillable = [
        'id_karyawan',
        'nomor_reimbursement',
        'judul_klaim',
        'kategori',
        'tgl_transaksi',
        'total_biaya',
        'keterangan',
        'bukti_struk',
        'status',
        'approved_by',
        'catatan_approver',
        'approved_at',
    ];

    protected $casts = [
        'tgl_transaksi' => 'date',
        'total_biaya' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
