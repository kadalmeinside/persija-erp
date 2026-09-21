<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AkunGl extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_akun_gl';

    protected $fillable = [
        'kode_akun',
        'nama_akun',
        'tipe_akun',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Hanya akun yang masih aktif.
     * Digunakan untuk dropdowns di form transaksi, PosAnggaran, dsb.
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Saldo normal berdasarkan tipe akun (sesuai kaidah akuntansi).
     * Akun Aset/Biaya: bertambah di Debit.
     * Akun Pendapatan/Modal/Kewajiban: bertambah di Kredit.
     */
    public function getSaldoNormalAttribute(): string
    {
        $debitTypes = ['Aset', 'Biaya', 'Biaya Modal'];
        return in_array($this->tipe_akun, $debitTypes) ? 'Debit' : 'Kredit';
    }

    protected $appends = ['saldo_normal'];

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * Relasi ke Pajak (satu Akun GL bisa digunakan oleh banyak aturan Pajak).
     */
    public function pajak()
    {
        return $this->hasMany(Pajak::class, 'id_akun_pajak');
    }
}
