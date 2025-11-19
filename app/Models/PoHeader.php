<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoHeader extends Model
{
    use HasFactory;

    protected $table = 'tbl_po_header';

    protected $fillable = [
        'id_vendor',
        'nomor_po',
        'tgl_po',
        'total_po',
        'status_po',
    ];

    /**
     * Relasi ke Vendor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'id_vendor');
    }

    /**
     * Relasi ke PO Detail (banyak item).
     */
    public function detail()
    {
        return $this->hasMany(PoDetail::class, 'id_po');
    }

    /**
     * Relasi ke GRN (satu PO bisa punya banyak GRN).
     */
    public function grn()
    {
        return $this->hasMany(GrnHeader::class, 'id_po');
    }
}