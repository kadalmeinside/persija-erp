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
        'potongan_pph21',
        'gaji_bersih',
        'id_jurnal',
    ];

    /**
     * Relasi kembali ke Header Payroll.
     */
    public function payrollHeader()
    {
        return $this->belongsTo(PayrollHeader::class, 'id_payroll');
    }

    /**
     * Relasi ke Karyawan.
     */
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    /**
     * Relasi ke Jurnal (log ini menghasilkan 1 jurnal).
     */
    public function jurnalHeader()
    {
        return $this->belongsTo(JurnalHeader::class, 'id_jurnal');
    }
}