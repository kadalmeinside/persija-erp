<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalHeader extends Model
{
    use HasFactory;

    protected $table = 'tbl_jurnal_header';

    protected $fillable = [
        'nomor_jurnal',
        'tgl_jurnal',
        'deskripsi_jurnal',
        'tipe_transaksi',
        'status',
        'created_by',
        'sumber_modul',
        'id_referensi_sumber'
    ];

    public function detail()
    {
        return $this->hasMany(JurnalDetail::class, 'id_jurnal');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
