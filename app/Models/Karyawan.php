<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_karyawan';

    protected $fillable = [
        'user_id',
        'id_departemen',
        'nomor_induk_karyawan', // NIK
        'nama_lengkap',
        'foto',
        'jabatan',
        'gaji_pokok',
        'status_ptkp',
        'jenis_kelamin',
        'info_bank',
        'tgl_bergabung',
        'tempat_lahir',
        'tgl_lahir',
        'alamat',
        'status_karyawan'
    ];

    protected $appends = ['foto_url', 'tanggal_berakhir_kontrak'];

    /**
     * Get dynamic photo URL (uploaded photo or UI Avatar)
     */
    public function getFotoUrlAttribute()
    {
        if ($this->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }
        
        $name = urlencode($this->nama_lengkap ?? 'Karyawan');
        return "https://ui-avatars.com/api/?name={$name}&color=7F9CF5&background=EBF4FF";
    }

    /**
     * CATATAN: 'primary_bank' SENGAJA tidak ada di $appends untuk menghindari N+1 query.
     * Accessor getPrimaryBankAttribute() tetap tersedia, tapi harus di-trigger manual:
     *   - Saat butuh di collection: $karyawan->append('primary_bank')
     *   - Saat butuh di single model: $karyawan->primary_bank
     */

    /**
     * Relasi ke User (Login).
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Relasi ke Departemen.
     */
    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    /**
     * Relasi ke Pengajuan (sebagai Pembuat Pengajuan).
     */
    public function pengajuan()
    {
        return $this->hasMany(PengajuanHeader::class, 'id_pengaju');
    }

    /**
     * Relasi ke Pengajuan (sebagai Penerima Uang Muka).
     * (Menambahkan ini agar bisa melacak pengajuan mana yang uangnya diterima karyawan ini)
     */
    public function pengajuanDiterima()
    {
        return $this->hasMany(PengajuanHeader::class, 'id_karyawan_penerima');
    }

    /**
     * Relasi ke Rekening Bank (Polymorphic).
     * Menghubungkan Karyawan ke tabel tbl_rekening_bank.
     */
    public function rekeningBank()
    {
        return $this->morphMany(RekeningBank::class, 'owner');
    }
    
    /**
     * Helper Accessor untuk mengambil rekening utama.
     * Cara pakai di controller/view: $karyawan->primary_bank
     */
    public function getPrimaryBankAttribute()
    {
        // Prioritaskan yang di-flag 'is_primary', jika tidak ada ambil yang pertama
        return $this->rekeningBank()->where('is_primary', true)->first() ?? $this->rekeningBank()->first();
    }

    /**
     * Relasi ke Saldo Cuti.
     */
    public function saldoCuti()
    {
        return $this->hasMany(\App\Models\SaldoCuti::class, 'id_karyawan');
    }

    /**
     * Relasi ke Tugas (sebagai penerima tugas).
     */
    public function tasksAssigned()
    {
        return $this->hasMany(Task::class, 'id_karyawan_assignee');
    }

    /**
     * Relasi ke Tugas (sebagai pembuat tugas).
     */
    public function tasksCreated()
    {
        return $this->hasMany(Task::class, 'id_karyawan_creator');
    }

    /**
     * Relasi ke Riwayat Karir Karyawan.
     */
    public function riwayatKarir()
    {
        return $this->hasMany(RiwayatKarir::class, 'id_karyawan')->orderBy('tanggal_efektif', 'desc');
    }

    public function latestRiwayatKarir()
    {
        return $this->hasOne(RiwayatKarir::class, 'id_karyawan')->ofMany('tanggal_efektif', 'max');
    }

    public function getTanggalBerakhirKontrakAttribute()
    {
        return $this->latestRiwayatKarir ? $this->latestRiwayatKarir->tanggal_berakhir_kontrak : null;
    }
}