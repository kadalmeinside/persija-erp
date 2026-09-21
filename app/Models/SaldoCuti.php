<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaldoCuti extends Model
{
    use HasFactory;

    protected $table = 'tbl_saldo_cuti';

    protected $guarded = ['id'];

    protected $casts = [
        'tahun_periode' => 'integer',
        'saldo_awal' => 'integer',
        'saldo_terpakai' => 'integer',
        'saldo_akhir' => 'integer'
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function jenisCuti()
    {
        return $this->belongsTo(JenisCuti::class, 'id_jenis_cuti');
    }
}
