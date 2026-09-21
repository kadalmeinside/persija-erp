<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_vendor';

    // Hapus field bank dari fillable karena sudah pindah tabel
    protected $fillable = [
        'kode_vendor',
        'nama_vendor',
        'kategori_vendor',
        'alamat_vendor',
        'kota',
        'telepon_vendor', 
        'email_vendor', 
        'npwp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pengajuan()
    {
        return $this->hasMany(PengajuanHeader::class, 'id_vendor_penerima');
    }

    /**
     * Relasi ke Rekening Bank
     */
    public function rekeningBank()
    {
        return $this->morphMany(RekeningBank::class, 'owner');
    }

    /**
     * Helper untuk mengambil rekening utama
     */
    public function getPrimaryBankAttribute()
    {
        return $this->rekeningBank()->where('is_primary', true)->first() ?? $this->rekeningBank()->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}