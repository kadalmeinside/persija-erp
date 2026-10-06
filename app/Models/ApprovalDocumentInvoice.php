<?php
namespace App\Models;
class ApprovalDocumentInvoice extends ApprovalDocumentLink
{
    protected $table = 'tbl_approval_document_invoice';
    public function document() { return $this->belongsTo(InvoiceHeader::class, 'document_id'); }
}
