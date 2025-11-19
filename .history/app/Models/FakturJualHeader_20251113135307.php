<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FakturJualHeader extends Model
{
    use HasFactory;

    protected $table = 'tbl_faktur_jual_header';

    protected $fillable = [
        'id_customer',
        'nomor_faktur',
        'tgl_faktur',
        'total_faktur',
        'status_pembayaran',
    ];

    /**
     * Relasi ke Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    /**
     * Relasi ke Detail Faktur Jual (banyak item).
     */
    public function detail()
    {
        return $this->hasMany(FakturJualDetail::class, 'id_faktur_jual');
    }

    /**
     * Relasi ke Penerimaan Pembayaran (satu faktur bisa dibayar berkali-kali).
     */
    public function penerimaanBayar()
    {
        return $this->hasMany(PenerimaanBayar::class, 'id_faktur_jual');
    }
}