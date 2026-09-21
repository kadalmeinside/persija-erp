<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LaporanPenggunaan extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_laporan_penggunaan';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pengajuan_uam', 
        'id_pelapor',
        'tgl_laporan',
        'total_realisasi_aktual',
        'selisih', 
        'bukti_pengembalian_path',
        'id_verifier',
        'verified_at',
        'verify_uuid'
    ];

    protected $casts = [
        'tgl_laporan' => 'date',
        'total_realisasi_aktual' => 'decimal:2',
        'selisih' => 'decimal:2',
        'verified_at' => 'datetime',
    ];


    /**
     * Relasi ke Header Pengajuan (Uang Muka yang diajukan).
     */
    public function pengajuan()
    {
        return $this->belongsTo(PengajuanHeader::class, 'id_pengajuan_uam');
    }

    /**
     * Relasi ke Karyawan (Pelapor).
     */
    public function pelapor()
    {
        return $this->belongsTo(Karyawan::class, 'id_pelapor');
    }

    /**
     * Relasi ke Karyawan (Verifier Finance).
     */
    public function verifier()
    {
        return $this->belongsTo(Karyawan::class, 'id_verifier');
    }

    /**
     * Relasi ke Detail Laporan (banyak bon).
     */
    public function detail()
    {
        return $this->hasMany(LaporanDetail::class, 'id_laporan');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}