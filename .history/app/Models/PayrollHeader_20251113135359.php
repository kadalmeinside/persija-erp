<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollHeader extends Model
{
    use HasFactory;

    protected $table = 'tbl_payroll_header';

    protected $fillable = [
        'periode_bulan',
        'periode_tahun',
        'status_payroll',
    ];

    /**
     * Relasi ke Detail Gaji (satu siklus punya banyak detail karyawan).
     */
    public function detail()
    {
        return $this->hasMany(PayrollDetail::class, 'id_payroll');
    }
}