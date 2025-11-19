<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetMaster extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_budget_master';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_periode_anggaran',
        'id_departemen',
        'id_akun',
        'id_program',
        'anggaran_total_tahun',
        'anggaran_terikat_ytd',
        'anggaran_realisasi_ytd',
    ];

    /**
     * Relasi ke periode.
     */
    public function periode()
    {
        return $this->belongsTo(PeriodeAnggaran::class, 'id_periode_anggaran');
    }

    /**
     * Relasi ke pacing vertikal
     */
    public function details() 
    {
        return $this->hasMany(BudgetDetail::class, 'id_budget_master');
    }

    /**
     * Relasi ke Departemen.
     */
    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    /**
     * Relasi ke AkunGl.
     */
    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun');
    }

    /**
     * Relasi ke ProgramKerja.
     */
    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program');
    }
}
