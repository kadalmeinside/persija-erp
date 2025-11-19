<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarisStok extends Model
{
    use HasFactory;

    protected $table = 'tbl_inventaris_stok';

    protected $fillable = [
        'id_item',
        'id_gudang',
        'stok_qty_on_hand',
    ];

    /**
     * Relasi ke Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }

    /**
     * Relasi ke Gudang.
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang');
    }
}