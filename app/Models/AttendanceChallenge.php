<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceChallenge extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'security_metadata' => 'array',
    ];
}
