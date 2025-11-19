<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_po_detail';

    protected $fillable = [
        'id_po',
        'id_item',
        'kuantitas',
        'harga_satuan',
    ];

    /**
     * Relasi kembali ke PO Header.
     */
    public function poHeader()
    {
        return $this->belongsTo(PoHeader::class, 'id_po');
    }

    /**
     * Relasi ke Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}