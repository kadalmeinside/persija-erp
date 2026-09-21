<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosAnggaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_pos_anggaran';

    protected $fillable = [
        'id_program_kerja',
        'id_akun_gl',
        'is_active',
        'catatan_arsip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Hanya pos anggaran yang masih aktif.
     * Digunakan untuk dropdown/pilihan di form baru.
     */
    public function scopeAktif($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Termasuk yang diarsipkan (is_active = false).
     * Digunakan untuk tampilan manajemen & laporan historis.
     */
    public function scopeIncludeArsip($query)
    {
        return $query; // tanpa filter is_active
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program_kerja');
    }

    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun_gl');
    }

    public function delegasi()
    {
        return $this->belongsToMany(Departemen::class, 'tbl_pos_anggaran_delegasi', 'id_pos_anggaran', 'id_departemen');
    }
}
