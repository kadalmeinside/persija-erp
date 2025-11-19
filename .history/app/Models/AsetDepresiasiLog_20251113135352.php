<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsetDepresiasiLog extends Model
{
    use HasFactory;

    protected $table = 'tbl_aset_depresiasi_log';

    protected $fillable = [
        'id_aset',
        'periode_bulan',
        'periode_tahun',
        'nilai_depresiasi_bulan',
        'id_jurnal',
    ];

    /**
     * Relasi ke Master Aset.
     */
    public function asetMaster()
    {
        return $this->belongsTo(AsetMaster::class, 'id_aset');
    }

    /**
     * Relasi ke Jurnal (log ini menghasilkan 1 jurnal).
     */
    public function jurnalHeader()
    {
        return $this->belongsTo(JurnalHeader::class, 'id_jurnal');
    }
}