<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'tbl_payroll';

    use \Spatie\Activitylog\Traits\LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'bulan_periode',
        'tahun',
        'tgl_payroll',
        'status',
        'tipe_payroll', // New
        'id_program',   // New
        'total_gaji_kotor',
        'total_potongan',
        'total_gaji_bersih',
        'id_user_pembuat',
        'approved_by',
        'tgl_disetujui'
    ];

    protected $casts = [
        'status' => \App\Enums\PayrollStatus::class,
    ];

    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program');
    }
    public function details()
    {
        return $this->hasMany(PayrollDetail::class, 'id_payroll');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
