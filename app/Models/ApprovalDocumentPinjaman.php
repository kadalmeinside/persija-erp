<?php
namespace App\Models;
class ApprovalDocumentPinjaman extends ApprovalDocumentLink
{
    protected $table = 'tbl_approval_document_pinjaman';
    public function document() { return $this->belongsTo(Pinjaman::class, 'document_id'); }
}
