<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturVendor extends Model
{
    use HasFactory;

    protected $table = 'tbl_faktur_vendor';

    protected $fillable = [
        'id_vendor',
        'id_po',
        'nomor_faktur',
        'tgl_faktur',
        'total_faktur',
    ];

    /**
     * Relasi ke Vendor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'id_vendor');
    }

    /**
     * Relasi ke PO Header.
     */
    public function poHeader()
    {
        return $this->belongsTo(PoHeader::class, 'id_po');
    }
}