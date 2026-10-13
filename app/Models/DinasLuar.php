<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DinasLuar extends Model
{
    protected $table = 'tbl_dinas_luar';

    protected $fillable = [
        'id_karyawan',
        'nomor_surat_tugas',
        'nama_kegiatan',
        'lokasi_tujuan',
        'tgl_mulai',
        'tgl_selesai',
        'keterangan',
        'surat_tugas_lampiran',
        'status',
        'approved_by',
        'catatan_approver',
        'approved_at',
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
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
