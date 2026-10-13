<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmutableAuditLog extends Model
{
    protected $table = 'tbl_immutable_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'sequence_number',
        'prev_hash',
        'current_hash',
        'entity_type',
        'entity_id',
        'action',
        'actor_id',
        'actor_name',
        'payload',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
