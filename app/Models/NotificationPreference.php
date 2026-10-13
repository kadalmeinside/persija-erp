<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'tbl_notification_preferences';

    protected $fillable = [
        'user_id',
        'approval_notifications',
        'leave_notifications',
        'attendance_notifications',
        'task_notifications',
        'announcement_notifications',
    ];

    protected $casts = [
        'approval_notifications' => 'boolean',
        'leave_notifications' => 'boolean',
        'attendance_notifications' => 'boolean',
        'task_notifications' => 'boolean',
        'announcement_notifications' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
