<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KasBank extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_kas_bank';

    protected $fillable = [
        'nama_bank',      // Contoh: "Bank BCA Operasional"
        'nomor_rekening', // Contoh: "123-456-789"
        'atas_nama',      // Contoh: "PT Persija Jaya"
        'id_akun_gl',     // Link ke COA untuk penjurnalan otomatis
        'saldo_awal',     // Saldo Awal saat pembukaan sistem
        'id_jurnal_saldo_awal', // Link ke Jurnal Saldo Awal
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Akun GL (Chart of Accounts).
     * Mengetahui akun akuntansi mana yang harus dikredit saat uang keluar dari sini.
     */
    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_gl');
    }
}