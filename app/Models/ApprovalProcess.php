<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalProcess extends Model
{
    use HasFactory;

    protected $table = 'tbl_approval_process';

    protected $guarded = ['id'];

    protected $casts = [
        'tgl_aksi' => 'datetime',
        'level_order' => 'integer'
    ];

    // Relasi ke Header Pengajuan
    public function pengajuan()
    {
        return $this->belongsTo(PengajuanHeader::class, 'id_pengajuan');
    }

    // Relasi ke Pengajuan Cuti
    public function cuti()
    {
        return $this->belongsTo(PengajuanCuti::class, 'id_cuti');
    }

    // Relasi ke Pinjaman
    public function pinjaman()
    {
        return $this->belongsTo(Pinjaman::class, 'id_pinjaman');
    }

    // Relasi ke Invoice
    public function invoice()
    {
        return $this->belongsTo(InvoiceHeader::class, 'id_invoice');
    }

    // Relasi ke Target Karyawan (Yang diminta approve sesuai Rule)
    public function targetKaryawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_target');
    }

    // Relasi ke Action Karyawan (Yang melakukan klik approve/reject)
    public function actionKaryawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan_action');
    }
    
    // Alias untuk backward compatibility (jika ada kode lama yang pakai 'approver')
    public function approver()
    {
        // Mengembalikan Action Karyawan jika sudah ada, atau Target jika belum
        return $this->id_karyawan_action 
            ? $this->belongsTo(Karyawan::class, 'id_karyawan_action')
            : $this->belongsTo(Karyawan::class, 'id_karyawan_target');
    }
}