<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\LogOptions;

class PengajuanHeader extends Model
{
    use HasFactory; 

    protected $table = 'tbl_pengajuan_header';

    use \Spatie\Activitylog\Traits\LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'nomor_pengajuan',
        'judul_pengajuan',
        'id_pengaju',
        'id_departemen',
        'tgl_pengajuan',
        'tipe_pengajuan',
        'total_nominal_diajukan',
        'status_global',
        'catatan_header',
        'attachment_path',
        'metode_pembayaran',
        'id_vendor_penerima',
        
        // Snapshot & Penerima
        'id_karyawan_penerima',
        'bank_tujuan',
        'no_rek_tujuan',
        'atas_nama_tujuan',

        // Petty Cash
        'id_kas_kecil', // FK ke tbl_kas_bank — hanya untuk tipe PettyCash
        'is_open_coa',
    ];

    protected $appends = ['is_butuh_laporan'];

    protected $casts = [
        'tgl_pengajuan' => 'date',
        'total_nominal_diajukan' => 'decimal:2',
        'status_global' => \App\Enums\PengajuanStatus::class,
        'metode_pembayaran' => \App\Enums\PaymentMethod::class,
        'is_open_coa' => 'boolean',
    ];


    public function pengaju()
    {
        return $this->belongsTo(Karyawan::class, 'id_pengaju');
    }

    public function karyawanPenerima()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_penerima');
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    public function vendorPenerima()
    {
        return $this->belongsTo(Vendor::class, 'id_vendor_penerima');
    }

    public function detail()
    {
        return $this->hasMany(PengajuanDetail::class, 'id_pengajuan');
    }

    public function approvalProcess()
    {
        return $this->hasMany(ApprovalProcess::class, 'id_pengajuan')->orderBy('level_order', 'asc');
    }

    public function currentStep()
    {
        return $this->hasOne(ApprovalProcess::class, 'id_pengajuan')->where('status', 'Pending');
    }

    public function pembayaran()
    {
        return $this->hasMany(PengajuanPembayaran::class, 'id_pengajuan')->orderBy('tgl_bayar', 'desc');
    }

    /**
     * Total yang sudah dibayarkan.
     *
     * ✅ Jika relasi 'pembayaran' sudah di-eager load (via with/load), gunakan collection sum
     *    untuk menghindari query tambahan. Jika belum, fallback ke aggregation query.
     * ✅ Jika menggunakan withSum('pembayaran as total_dibayar', ...), value tersebut
     *    akan menimpa accessor ini karena diset sebagai attribute langsung.
     */
    public function getTotalDibayarAttribute(): float
    {
        // Jika sudah di-eager load via withSum — value langsung tersedia di attributes
        if (array_key_exists('total_dibayar', $this->attributes)) {
            return (float) $this->attributes['total_dibayar'];
        }

        // Jika relasi pembayaran sudah ter-load, hitung dari collection (0 query)
        if ($this->relationLoaded('pembayaran')) {
            return (float) $this->pembayaran->sum('nominal_bayar');
        }

        // Fallback: query ke DB (hanya terjadi jika tidak dalam konteks list/index)
        return (float) $this->pembayaran()->sum('nominal_bayar');
    }

    public function getSisaTagihanAttribute(): float
    {
        return (float) $this->total_nominal_diajukan - $this->total_dibayar;
    }

    public function getStatusPembayaranAttribute(): string
    {
        $dibayar = $this->total_dibayar;
        if ($dibayar == 0) return 'Unpaid';
        if (round($dibayar, 2) < round((float) $this->total_nominal_diajukan, 2)) return 'Partial';
        return 'Paid';
    }

    public function laporanPenggunaan()
    {
        return $this->hasOne(LaporanPenggunaan::class, 'id_pengajuan_uam');
    }

    /** Relasi ke Kas Kecil (KasBank) — hanya untuk tipe PettyCash */
    public function kasKecil()
    {
        return $this->belongsTo(\App\Models\KasBank::class, 'id_kas_kecil');
    }

    public function getIsButuhLaporanAttribute(): bool
    {
        $hasLaporan = $this->relationLoaded('laporanPenggunaan') 
            ? $this->laporanPenggunaan !== null 
            : $this->laporanPenggunaan()->exists();

        return $this->tipe_pengajuan === 'UangMuka' 
            && in_array($this->status_global->value ?? $this->status_global, ['Paid', 'Revision']) 
            && !$hasLaporan;
    }

    public function scopeButuhLaporan($query)
    {
        return $query->where('tipe_pengajuan', 'UangMuka')
                     ->whereIn('status_global', ['Paid', 'Revision'])
                     ->whereDoesntHave('laporanPenggunaan');
    }
}