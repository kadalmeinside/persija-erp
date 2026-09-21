<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_jurnal_detail';

    protected $fillable = [
        'id_jurnal',
        'id_akun',
        'debit',
        'kredit',
        'keterangan_baris'
    ];

    public function header()
    {
        return $this->belongsTo(JurnalHeader::class, 'id_jurnal');
    }

    public function akun()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun');
    }
}
