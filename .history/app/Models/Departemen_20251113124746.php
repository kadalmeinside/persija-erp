<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departemen extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_departemen';

    /**
     * Relasi ke Karyawan (satu Dept memiliki banyak Karyawan).
     */
    public function karyawan()
    {
        return $this->hasMany(Karyawan::class, 'id_departemen');
    }

    /**
     * Relasi ke Budget (satu Dept memiliki banyak baris Budget).
     */
    public function budgetMaster()
    {
        return $this->hasMany(BudgetMaster::class, 'id_departemen');
    }
}
