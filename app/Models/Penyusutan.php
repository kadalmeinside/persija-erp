<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyusutan extends Model
{
    use HasFactory;

    protected $table = 'tbl_penyusutan';

    protected $fillable = [
        'id_aset',
        'tgl_penyusutan',
        'nilai_buku_awal',
        'nilai_penyusutan',
        'nilai_buku_akhir',
        'is_posted',
        'jurnal_ref'
    ];

    public function aset()
    {
        return $this->belongsTo(Aset::class, 'id_aset');
    }
}
