<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PengajuanPembayaran extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'tbl_pengajuan_pembayaran';

    protected $fillable = [
        'id_pengajuan',
        'tgl_bayar',
        'nominal_bayar',
        'bukti_bayar_path',
        'id_kas_bank', 
        'catatan',
        'created_by'
    ];

    protected $casts = [
        'tgl_bayar' => 'date',
        'nominal_bayar' => 'decimal:2',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanHeader::class, 'id_pengajuan');
    }

    /**
     * Relasi ke Sumber Dana (Kas/Bank Perusahaan)
     */
    public function kasBank()
    {
        return $this->belongsTo(KasBank::class, 'id_kas_bank');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}