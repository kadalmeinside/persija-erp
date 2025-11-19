<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPenggunaan extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_laporan_penggunaan';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pengajuan_uam',
        'id_pelapor',
        'tgl_laporan',
        'total_realisasi_aktual',
        'selih',
    ];

    /**
     * Relasi ke Header Pengajuan (Uang Muka yang diajukan).
     */
    public function pengajuanUangMuka()
    {
        return $this->belongsTo(PengajuanHeader::class, 'id_pengajuan_uam');
    }

    /**
     * Relasi ke Karyawan (Pelapor).
     */
    public function pelapor()
    {
        return $this->belongsTo(Karyawan::class, 'id_pelapor');
    }

    /**
     * Relasi ke Detail Laporan (banyak bon).
     */
    public function detail()
    {
        return $this->hasMany(LaporanDetail::class, 'id_laporan');
    }
}