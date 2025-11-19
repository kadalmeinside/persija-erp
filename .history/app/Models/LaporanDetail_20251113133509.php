<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanDetail extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_laporan_detail';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_laporan',
        'deskripsi_bon',
        'nominal_bon',
        'id_departemen_beban',
        'id_program_beban',
        'id_akun_beban',
        'id_pajak',
    ];

    /**
     * Relasi kembali ke Header Laporan.
     */
    public function laporanHeader()
    {
        return $this->belongsTo(LaporanPenggunaan::class, 'id_laporan');
    }

    /**
     * Relasi ke Departemen (yang dibebani biaya).
     */
    public function departemenBeban()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen_beban');
    }

    /**
     * Relasi ke Program Kerja (yang dibebani biaya).
     */
    public function programBeban()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program_beban');
    }

    /**
     * Relasi ke Akun GL (yang dibebani biaya).
     */
    public function akunBeban()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_beban');
    }

    /**
     * Relasi ke Pajak.
     */
    public function pajak()
    {
        return $this->belongsTo(Pajak::class, 'id_pajak');
    }
}