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
        // 'id_departemen', // Removed for Centralized Budgeting
        'id_pos_anggaran',
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
     * Relasi ke Pos Anggaran.
     */
    public function posAnggaran()
    {
        return $this->belongsTo(PosAnggaran::class, 'id_pos_anggaran');
    }
}
