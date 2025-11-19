<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanHeader extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_pengajuan_header';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_pengajuan',
        'id_pengaju',
        'id_departemen',
        'tgl_pengajuan',
        'tipe_pengajuan',
        'total_nominal_diajukan',
        'status_global',
        'catatan_header',
    ];

    /**
     * Relasi ke Karyawan (Pengaju).
     */
    public function pengaju()
    {
        return $this->belongsTo(Karyawan::class, 'id_pengaju');
    }

    /**
     * Relasi ke Departemen.
     */
    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    /**
     * Relasi ke Detail Pengajuan (satu Header memiliki banyak Detail).
     */
    public function detail()
    {
        return $this->hasMany(PengajuanDetail::class, 'id_pengajuan');
    }

    /**
     * Relasi ke Log Persetujuan.
     */
    public function logPersetujuan()
    {
        return $this->hasMany(LogPersetujuan::class, 'id_pengajuan');
    }

    /**
     * Relasi ke Laporan Penggunaan (jika ini adalah Uang Muka).
     */
    public function laporanPenggunaan()
    {
        return $this->hasOne(LaporanPenggunaan::class, 'id_pengajuan_uam');
    }
}
