<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarisKeluar extends Model
{
    use HasFactory;

    protected $table = 'tbl_inventaris_keluar';

    protected $fillable = [
        'id_item',
        'id_gudang',
        'id_departemen_pemakai',
        'kuantitas_keluar',
        'tgl_keluar',
    ];

    /**
     * Relasi ke Departemen (Pemakai).
     */
    public function departemenPemakai()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen_pemakai');
    }

    /**
     * Relasi ke Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}