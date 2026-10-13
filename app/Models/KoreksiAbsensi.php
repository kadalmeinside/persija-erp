<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KoreksiAbsensi extends Model
{
    protected $table = 'tbl_koreksi_absensi';

    protected $fillable = [
        'id_karyawan',
        'id_absensi',
        'tanggal',
        'waktu_masuk_baru',
        'waktu_keluar_baru',
        'alasan',
        'bukti_lampiran',
        'status',
        'approved_by',
        'catatan_approver',
        'approved_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'approved_at' => 'datetime',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class, 'id_absensi');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
