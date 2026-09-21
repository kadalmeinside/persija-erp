<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'tbl_pelanggan';

    protected $fillable = [
        'kode_pelanggan',
        'nama_pelanggan',
        'email',
        'telepon',
        'alamat',
        'npwp',
        'is_active'
    ];

    public function invoices()
    {
        return $this->hasMany(InvoiceHeader::class, 'id_pelanggan');
    }
}
