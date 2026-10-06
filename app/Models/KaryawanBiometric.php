<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaryawanBiometric extends Model
{
    protected $table = 'tbl_karyawan_biometrics';

    protected $fillable = [
        'id_karyawan',
        'face_descriptor',
    ];

    protected $casts = [
        'face_descriptor' => 'encrypted',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
