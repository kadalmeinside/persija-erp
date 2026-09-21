<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PengajuanDetail extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Nama tabel yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'tbl_pengajuan_detail';

    /**
     * Kolom yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_pengajuan',
        'deskripsi_item',
        'nominal_item',
        'id_program',
        'id_akun',
        'id_tax_type',
        'rate_pajak',
        'nominal_pajak',
        'attachment_path',
    ];

    /**
     * Relasi kembali ke Header.
     */
    public function pengajuanHeader()
    {
        return $this->belongsTo(PengajuanHeader::class, 'id_pengajuan');
    }

    /**
     * Relasi ke Program Kerja.
     */
    public function programKerja()
    {
        return $this->belongsTo(ProgramKerja::class, 'id_program');
    }

    /**
     * Relasi ke Akun GL.
     */
    public function akunGl()
    {
        return $this->belongsTo(AkunGl::class, 'id_akun');
    }

    /**
     * Relasi ke Pajak (TaxType).
     */
    public function pajak()
    {
        return $this->belongsTo(TaxType::class, 'id_tax_type');
    }

    /**
     * Relasi ke Vendor.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'id_vendor');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}