<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollDetail extends Model
{
    use HasFactory;

    protected $table = 'tbl_payroll_detail';

    protected $fillable = [
        'id_payroll',
        'id_karyawan',
        'gaji_pokok',
        'total_tunjangan',
        'honorarium',
        'jumlah_sesi',
        'rate_per_sesi',
        'lembur',
        'total_potongan',
        'gaji_bersih',
        'rincian_komponen',
        'id_program',
        'id_komponen_gaji'
    ];

    protected $casts = [
        'rincian_komponen' => 'array',
    ];

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'id_payroll');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program');
    }

    public function komponenGaji()
    {
        return $this->belongsTo(GajiKomponen::class, 'id_komponen_gaji');
    }
}