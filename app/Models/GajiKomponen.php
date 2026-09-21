<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GajiKomponen extends Model
{
    use HasFactory;

    protected $table = 'tbl_gaji_komponen';

    protected $fillable = [
        'nama_komponen',
        'tipe',
        'is_taxable',
        'id_akun_gl'
    ];

    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_gl');
    }
}
