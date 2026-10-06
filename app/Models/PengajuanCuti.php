<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $table = 'tbl_pengajuan_cuti';

    protected $fillable = [
        'id_karyawan', 'id_jenis_cuti', 'tgl_mulai', 'tgl_selesai',
        'jumlah_hari', 'alasan', 'lampiran_path', 'status',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
        'jumlah_hari' => 'integer',
        'cancelled_at' => 'datetime',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }

    public function jenisCuti()
    {
        return $this->belongsTo(JenisCuti::class, 'id_jenis_cuti');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'id_approver');
    }

    public function approvalProcess()
    {
        return $this->hasManyThrough(
            ApprovalProcess::class,
            ApprovalDocument::class,
            'document_id',
            'id_approval_document',
            'id',
            'id'
        )->where('tbl_approval_documents.document_type', 'Cuti')
          ->orderBy('level_order', 'asc');
    }
}
