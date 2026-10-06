<?php
namespace App\Models;
class ApprovalDocumentPengajuan extends ApprovalDocumentLink
{
    protected $table = 'tbl_approval_document_pengajuan';
    public function document() { return $this->belongsTo(PengajuanHeader::class, 'document_id'); }
}
