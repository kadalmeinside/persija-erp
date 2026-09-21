<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Enums\AngsuranStatus;

class AngsuranPinjaman extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tbl_angsuran_pinjaman';

    protected $fillable = [
        'id_pinjaman',
        'bulan_periode',
        'jumlah_angsuran',
        'status_bayar',
        'id_payroll_detail'
    ];

    protected $casts = [
        'jumlah_angsuran' => 'decimal:2',
        'status_bayar'    => AngsuranStatus::class,
    ];

    public function pinjaman()
    {
        return $this->belongsTo(Pinjaman::class, 'id_pinjaman');
    }

    public function payrollDetail()
    {
        return $this->belongsTo(PayrollDetail::class, 'id_payroll_detail');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
