<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceHeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_invoice_header';

    protected $fillable = [
        'nomor_invoice',
        'id_departemen',
        'id_pelanggan',
        'tgl_invoice',
        'tgl_jatuh_tempo',
        'status',
        'subtotal',
        'id_tax_ppn',
        'ppn_rate',
        'ppn_amount',
        'id_tax_pph',
        'pph_rate',
        'pph_amount',
        'total_tagihan',
        'sisa_tagihan',
        'catatan',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status'        => \App\Enums\InvoiceStatus::class,
        'tgl_invoice'   => 'date',
        'tgl_jatuh_tempo' => 'date',
        'approved_at'   => 'datetime',
    ];

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'id_departemen');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan');
    }

    public function detail()
    {
        return $this->hasMany(InvoiceDetail::class, 'id_invoice');
    }

    public function pembayaran()
    {
        return $this->hasMany(PenerimaanPembayaran::class, 'id_invoice');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taxPpn()
    {
        return $this->belongsTo(TaxType::class, 'id_tax_ppn');
    }

    public function taxPph()
    {
        return $this->belongsTo(TaxType::class, 'id_tax_pph');
    }

    // -------------------------------------------------------------------------
    // Approval Relationships
    // -------------------------------------------------------------------------

    /** Semua langkah approval untuk invoice ini */
    public function approvalProcess()
    {
        return $this->hasManyThrough(
            ApprovalProcess::class,
            ApprovalDocument::class,
            'document_id',
            'id_approval_document',
            'id',
            'id'
        )->where('tbl_approval_documents.document_type', 'Invoice')
          ->orderBy('level_order');
    }

    /** Langkah approval yang sedang aktif (status = Pending) */
    public function currentApprovalStep()
    {
        return $this->approvalProcess()->where('status', 'Pending')->one();
    }

    /** User yang menyetujui final */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
