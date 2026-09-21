<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pinjaman extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tbl_pinjaman';

    protected $fillable = [
        'id_karyawan',
        'tanggal_pengajuan',
        'jumlah_pinjaman',
        'tenor_bulan',
        'bunga_persen',
        'jumlah_angsuran_per_bulan',
        'keterangan',
        'status',
        'approved_by',
        'approved_at',
        'approval_uuid' // Added
    ];

    protected $casts = [
        'tanggal_pengajuan' => 'date',
        'approved_at' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function angsuran()
    {
        return $this->hasMany(AngsuranPinjaman::class, 'id_pinjaman');
    }

    public function approvalProcess()
    {
        return $this->hasMany(ApprovalProcess::class, 'id_pinjaman');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
