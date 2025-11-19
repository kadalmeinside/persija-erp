<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsetMaster extends Model
{
    use HasFactory;

    protected $table = 'tbl_aset_master';

    protected $fillable = [
        'id_item',
        'nama_aset',
        'id_departemen_lokasi',
        'tgl_perolehan',
        'nilai_perolehan',
        'nilai_sisa',
        'masa_manfaat_bulan',
        'metode_depresiasi',
        'id_akun_aset',
        'id_akun_akumulasi',
        'id_akun_beban',
        'status_aset',
    ];

    /**
     * Relasi ke Departemen (lokasi aset).
     */
    public function departemenLokasi()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen_lokasi');
    }

    /**
     * Relasi ke Akun Aset (di Neraca).
     */
    public function akunAset()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_aset');
    }

    /**
     * Relasi ke Akun Akumulasi (di Neraca).
     */
    public function akunAkumulasi()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_akumulasi');
    }

    /**
     * Relasi ke Akun Beban (di Laba Rugi).
     */
    public function akunBeban()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_beban');
    }

    /**
     * Relasi ke Log Depresiasi (satu aset punya banyak log bulanan).
     */
    public function logDepresiasi()
    {
        return $this->hasMany(AsetDepresiasiLog::class, 'id_aset');
    }
}