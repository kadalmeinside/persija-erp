<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturJualDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_faktur_jual_detail';

    protected $fillable = [
        'id_faktur_jual',
        'id_item',
        'deskripsi_jasa',
        'kuantitas',
        'harga_satuan',
        'id_pajak',
    ];

    /**
     * Relasi kembali ke Header Faktur Jual.
     */
    public function fakturJualHeader()
    {
        return $this->belongsTo(FakturJualHeader::class, 'id_faktur_jual');
    }

    /**
     * Relasi ke Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}