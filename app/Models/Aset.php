<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'tbl_aset';

    protected $fillable = [
        'kode_aset',
        'nama_aset',
        'kategori',
        'tgl_perolehan',
        'harga_perolehan',
        'nilai_sisa',
        'umur_manfaat_bulan',
        'metode_penyusutan',
        'id_akun_aset',
        'id_akun_akumulasi_penyusutan',
        'id_akun_beban_penyusutan',
        'status',
        'deskripsi'
    ];

    public function akunAset()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_aset');
    }

    public function akunAkumulasi()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_akumulasi_penyusutan');
    }

    public function akunBeban()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_beban_penyusutan');
    }

    public function penyusutan()
    {
        return $this->hasMany(Penyusutan::class, 'id_aset')->orderBy('tgl_penyusutan', 'desc');
    }
}
