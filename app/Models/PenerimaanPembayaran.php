<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembayaran extends Model
{
    use HasFactory;

    protected $table = 'tbl_penerimaan_pembayaran';

    protected $fillable = [
        'id_invoice',
        'tgl_bayar',
        'nominal_bayar',
        'id_kas_bank',
        'bukti_bayar_path',
        'catatan',
        'created_by'
    ];

    public function invoice()
    {
        return $this->belongsTo(InvoiceHeader::class, 'id_invoice');
    }

    public function kasBank()
    {
        return $this->belongsTo(KasBank::class, 'id_kas_bank');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
