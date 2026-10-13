<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationLog extends Model
{
    protected $table = 'tbl_reconciliation_logs';

    protected $fillable = [
        'period',
        'module',
        'total_expected',
        'total_actual',
        'discrepancy',
        'status',
        'details',
        'reconciled_by',
    ];

    protected $casts = [
        'total_expected' => 'decimal:2',
        'total_actual' => 'decimal:2',
        'discrepancy' => 'decimal:2',
        'details' => 'array',
    ];

    public function reconciler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
