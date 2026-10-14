<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class KaryawanDokumen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_karyawan_dokumen';

    protected $fillable = [
        'id_karyawan',
        'kategori_dokumen',
        'judul_dokumen',
        'nomor_dokumen',
        'file_path',
        'file_size',
        'file_extension',
        'tanggal_terbit',
        'tanggal_kadaluarsa',
        'catatan',
        'uploaded_by'
    ];

    protected $casts = [
        'tanggal_terbit' => 'date',
        'tanggal_kadaluarsa' => 'date',
        'file_size' => 'integer',
    ];

    protected $appends = ['file_url', 'formatted_size'];

    public function getFileUrlAttribute()
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }

    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size) return '-';
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' ' . ($units[$i] ?? 'B');
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
