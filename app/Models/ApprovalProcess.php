<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_approval_process';

    protected $guarded = ['id'];

    protected $casts = [
        'tgl_aksi' => 'datetime',
        'level_order' => 'integer'
    ];

    public function approvalDocument()
    {
        return $this->belongsTo(ApprovalDocument::class, 'id_approval_document');
    }

    public function document()
    {
        return $this->approvalDocument?->resolveDocument();
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

    public function scopeForDocument($query, string $type, int $documentId)
    {
        $relation = match ($type) {
            'Pengajuan' => 'pengajuan',
            'Cuti' => 'cuti',
            'Pinjaman' => 'pinjaman',
            'Invoice' => 'invoice',
            default => throw new \InvalidArgumentException("Tipe dokumen tidak didukung: {$type}"),
        };

        return $query->whereHas('approvalDocument', fn ($q) => $q
            ->where('document_type', $type)
            ->whereHas($relation, fn ($link) => $link->where('document_id', $documentId)));
    }
}