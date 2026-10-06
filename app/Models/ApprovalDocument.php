<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalDocument extends Model
{
    use HasFactory;

    protected $table = 'tbl_approval_documents';

    protected $guarded = ['id'];

    public function processes()
    {
        return $this->hasMany(ApprovalProcess::class, 'id_approval_document');
    }

    public function pengajuan()
    {
        return $this->hasOne(ApprovalDocumentPengajuan::class, 'id_approval_document');
    }

    public function cuti()
    {
        return $this->hasOne(ApprovalDocumentCuti::class, 'id_approval_document');
    }

    public function pinjaman()
    {
        return $this->hasOne(ApprovalDocumentPinjaman::class, 'id_approval_document');
    }

    public function invoice()
    {
        return $this->hasOne(ApprovalDocumentInvoice::class, 'id_approval_document');
    }

    public function resolveDocument()
    {
        $relation = match ($this->document_type) {
            'Pengajuan' => $this->pengajuan(),
            'Cuti' => $this->cuti(),
            'Pinjaman' => $this->pinjaman(),
            'Invoice' => $this->invoice(),
            default => throw new \LogicException("Tipe dokumen approval tidak dikenal: {$this->document_type}"),
        };

        return $relation->with('document')->first()?->document;
    }
}
