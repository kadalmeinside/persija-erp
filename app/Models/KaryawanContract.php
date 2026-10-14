<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class KaryawanContract extends Model
{
    use HasFactory;

    protected $table = 'tbl_karyawan_contracts';

    protected $fillable = [
        'id_karyawan',
        'version_number',
        'nomor_kontrak',
        'contract_type',
        'judul_surat',
        'tanggal_mulai',
        'tanggal_selesai',
        'durasi_probation_bulan',
        'notice_period_hari',
        'jam_kerja_mingguan',
        'gaji_pokok',
        'working_scope',
        'contract_content',
        'rendered_content',
        'file_path',
        'status',
        'catatan_perubahan',
        'created_by',
    ];

    protected $casts = [
        'version_number'         => 'integer',
        'tanggal_mulai'          => 'date',
        'tanggal_selesai'        => 'date',
        'durasi_probation_bulan' => 'integer',
        'notice_period_hari'     => 'integer',
        'jam_kerja_mingguan'     => 'integer',
        'gaji_pokok'             => 'decimal:2',
        'working_scope'          => 'array',
    ];

    protected $appends = ['file_url', 'formatted_salary'];

    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    public function getFormattedSalaryAttribute(): string
    {
        return number_format($this->gaji_pokok ?? 0, 0, ',', '.');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
