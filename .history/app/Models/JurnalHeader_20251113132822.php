<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalHeader extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_jurnal_header';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor_jurnal',
        'tgl_jurnal',
        'deskripsi_jurnal',
        'sumber_modul',
        'id_referensi_sumber',
    ];

    /**
     * Relasi ke JurnalDetail (satu Header memiliki banyak Detail).
     */
    public function jurnalDetail()
    {
        return $this->hasMany(JurnalDetail::class, 'id_jurnal');
    }
}
