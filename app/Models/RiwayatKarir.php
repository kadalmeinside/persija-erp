<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatKarir extends Model
{
    use HasFactory;

    protected $table = 'tbl_riwayat_karir';

    protected $fillable = [
        'id_karyawan',
        'tipe_peristiwa',
        'tanggal_efektif',
        'tanggal_berakhir_kontrak',
        'id_departemen',
        'jabatan',
        'status_karyawan',
        'gaji_pokok',
        'catatan',
        'file_sk'
    ];

    protected $casts = [
        'tanggal_efektif' => 'date',
        'tanggal_berakhir_kontrak' => 'date',
        'gaji_pokok' => 'decimal:2'
    ];

    protected $appends = ['file_sk_url'];

    public function getFileSkUrlAttribute()
    {
        if ($this->file_sk && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->file_sk)) {
            return asset('storage/' . $this->file_sk);
        }
        return null;
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }
}
