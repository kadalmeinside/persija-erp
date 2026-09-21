<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramKerja extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_program_kerja';

    /**
     * Relasi ke Budget (satu Program memiliki banyak baris Budget).
     */
    protected $fillable = ['nama_program', 'id_departemen'];

    /**
     * Relasi ke Departemen (Program milik Departemen).
     */
    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    /**
     * Relasi ke Pos Anggaran (Program punya banyak Pos Anggaran/COA).
     */
    public function posAnggaran()
    {
        return $this->hasMany(PosAnggaran::class, 'id_program_kerja');
    }

    /**
     * Relasi ke Budget (satu Program memiliki banyak baris Budget).
     */
    public function budgetMaster()
    {
        return $this->hasMany(BudgetMaster::class, 'id_program');
    }
}
