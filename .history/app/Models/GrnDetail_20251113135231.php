<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrnDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_grn_detail';

    protected $fillable = [
        'id_grn',
        'id_item',
        'kuantitas_diterima',
    ];

    /**
     * Relasi kembali ke GRN Header.
     */
    public function grnHeader()
    {
        return $this->belongsTo(GrnHeader::class, 'id_grn');
    }

    /**
     * Relasi ke Item.
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }
}