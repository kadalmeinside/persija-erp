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
    public function budgetMaster()
    {
        return $this->hasMany(BudgetMaster::class, 'id_program');
    }
}
