<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalDetail extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_jurnal_detail';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_jurnal',
        'id_akun',
        'debit',
        'kredit',
    ];

    /**
     * Relasi ke JurnalHeader (banyak Detail dimiliki oleh satu Header).
     */
    public function jurnalHeader()
    {
        return $this->belongsTo(JurnalHeader::class, 'id_jurnal');
    }

    /**
     * Relasi ke AkunGl (satu baris Detail terhubung ke satu Akun).
     */
    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun');
    }
}
