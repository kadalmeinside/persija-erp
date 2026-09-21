<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxType extends Model
{
    protected $table = 'tbl_tax_types';

    protected $fillable = [
        'kode_pajak',
        'nama_pajak',
        'rate',
        'tipe',
        'id_akun_gl',
        'id_program',
        'is_active'
    ];

    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_gl');
    }

    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program');
    }
}
