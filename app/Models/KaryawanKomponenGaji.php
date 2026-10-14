<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KaryawanKomponenGaji extends Model
{
    use HasFactory;

    protected $table = 'tbl_karyawan_komponen_gaji';

    protected $fillable = [
        'id_karyawan',
        'id_komponen_gaji',
        'nominal',
        'keterangan',
        'is_active',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function komponenGaji()
    {
        return $this->belongsTo(GajiKomponen::class, 'id_komponen_gaji');
    }
}
