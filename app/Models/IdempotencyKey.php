<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdempotencyKey extends Model
{
    protected $table = 'tbl_idempotency_keys';

    protected $fillable = [
        'key',
        'user_id',
        'endpoint',
        'request_hash',
        'status',
        'response_code',
        'response_body',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'response_code' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
