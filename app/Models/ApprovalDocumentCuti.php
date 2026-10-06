<?php
namespace App\Models;
class ApprovalDocumentCuti extends ApprovalDocumentLink
{
    protected $table = 'tbl_approval_document_cuti';
    public function document() { return $this->belongsTo(PengajuanCuti::class, 'document_id'); }
}
