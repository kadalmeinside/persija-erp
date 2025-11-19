<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnHeader extends Model
{
    use HasFactory;

    protected $table = 'tbl_grn_header';

    protected $fillable = [
        'id_po',
        'id_gudang',
        'nomor_grn',
        'tgl_terima',
    ];

    /**
     * Relasi ke PO Header (opsional).
     */
    public function poHeader()
    {
        return $this->belongsTo(PoHeader::class, 'id_po');
    }

    /**
     * Relasi ke Gudang.
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang');
    }

    /**
     * Relasi ke GRN Detail (banyak item).
     */
    public function detail()
    {
        return $this->hasMany(GrnDetail::class, 'id_grn');
    }
}