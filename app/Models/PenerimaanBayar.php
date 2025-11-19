<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBayar extends Model
{
    use HasFactory;

    protected $table = 'tbl_penerimaan_bayar';

    protected $fillable = [
        'id_faktur_jual',
        'id_customer',
        'tgl_bayar',
        'jumlah_bayar',
        'metode_bayar',
    ];

    /**
     * Relasi ke Faktur Jual Header (opsional).
     */
    public function fakturJualHeader()
    {
        return $this->belongsTo(FakturJualHeader::class, 'id_faktur_jual');
    }

    /**
     * Relasi ke Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }
}