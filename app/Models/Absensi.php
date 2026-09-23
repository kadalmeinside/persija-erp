<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    protected $table = 'tbl_absensi';
    protected $guarded = [];

    protected $casts = [
        'is_dinas_luar' => 'boolean',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function lokasiKantor()
    {
        return $this->belongsTo(LokasiKantor::class, 'id_lokasi_kantor');
    }
}
