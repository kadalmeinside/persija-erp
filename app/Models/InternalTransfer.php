<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalTransfer extends Model
{
    use HasFactory;

    protected $table = 'tbl_internal_transfer';

    protected $fillable = [
        'nomor_transfer',
        'tgl_transfer',
        'from_kas_bank_id',
        'to_kas_bank_id',
        'nominal',
        'keterangan',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'id_jurnal',
    ];

    protected $casts = [
        'tgl_transfer' => 'date',
        'approved_at'  => 'datetime',
        'nominal'      => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function fromKasBank()
    {
        return $this->belongsTo(KasBank::class, 'from_kas_bank_id');
    }

    public function toKasBank()
    {
        return $this->belongsTo(KasBank::class, 'to_kas_bank_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function jurnal()
    {
        return $this->belongsTo(JurnalHeader::class, 'id_jurnal');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isDraft(): bool
    {
        return $this->status === 'Draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved';
    }
}
