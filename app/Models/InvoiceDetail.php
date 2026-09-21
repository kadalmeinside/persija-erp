<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_invoice_detail';

    protected $fillable = [
        'id_invoice',
        'deskripsi_item',
        'kuantitas',
        'harga_satuan',
        'total_harga',
        'id_akun_pendapatan'
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceHeader::class, 'id_invoice');
    }

    public function akunPendapatan()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_pendapatan');
    }
}
