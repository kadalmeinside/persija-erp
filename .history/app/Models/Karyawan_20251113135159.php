<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'tbl_karyawan';

    protected $fillable = [
        'user_id',
        'id_departemen',
        'nomor_induk_karyawan',
        'nama_lengkap',
        'jabatan',
        'gaji_pokok',
        'status_ptkp',
        'info_bank',
    ];

    /**
     * Relasi ke User (Login).
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Relasi ke Departemen.
     */
    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    /**
     * Relasi ke Pengajuan (sebagai Pengaju).
     */
    public function pengajuan()
    {
        return $this->hasMany(PengajuanHeader::class, 'id_pengaju');
    }
}