<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPersetujuan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_log_persetujuan';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pengajuan',
        'id_approver',
        'tgl_aksi',
        'status_aksi',
        'catatan_approver',
    ];

    /**
     * Relasi ke Header Pengajuan.
     */
    public function pengajuanHeader()
    {
        return $this->belongsTo(PengajuanHeader::class, 'id_pengajuan');
    }

    /**
     * Relasi ke Karyawan (Approver).
     */
    public function approver()
    {
        return $this->belongsTo(Karyawan::class, 'id_approver');
    }
}