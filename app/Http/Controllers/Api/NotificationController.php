<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\UserDeviceToken;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user notifications with unread count.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->input('limit', 30);
        $filter = $request->input('filter'); // 'all', 'unread'

        $query = $user->notifications();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->limit($limit)->get()->map(function ($n) {
            $data = is_array($n->data) ? $n->data : json_decode($n->data, true) ?? [];
            return [
                'id' => (string) $n->id,
                'type' => $data['type'] ?? class_basename($n->type),
                'title' => $data['title'] ?? 'Pemberitahuan Sistem',
                'message' => $data['message'] ?? $data['body'] ?? '-',
                'link' => $data['link'] ?? $data['deep_link'] ?? null,
                'data' => $data,
                'read_at' => $n->read_at?->toIso8601String(),
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
                'created_at_human' => $n->created_at?->diffForHumans() ?? '',
            ];
        });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'status' => 'success',
            'unread_count' => $unreadCount,
            'data' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi ditandai telah dibaca.',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'status' => 'success',
            'message' => 'Semua notifikasi berhasil ditandai sudah dibaca.',
            'unread_count' => 0,
        ]);
    }

    /**
     * Get user notification preferences.
     */
    public function getPreferences(Request $request)
    {
        $user = $request->user();
        $prefs = NotificationPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'approval_notifications' => true,
                'leave_notifications' => true,
                'attendance_notifications' => true,
                'task_notifications' => true,
                'announcement_notifications' => true,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => $prefs,
        ]);
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'approval_notifications' => 'sometimes|boolean',
            'leave_notifications' => 'sometimes|boolean',
            'attendance_notifications' => 'sometimes|boolean',
            'task_notifications' => 'sometimes|boolean',
            'announcement_notifications' => 'sometimes|boolean',
        ]);

        $prefs = NotificationPreference::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan notifikasi berhasil diperbarui.',
            'data' => $prefs,
        ]);
    }

    /**
     * Register or update mobile push device token (FCM).
     */
    public function registerDeviceToken(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:100',
        ]);

        UserDeviceToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_token' => $validated['device_token'],
            ],
            [
                'device_type' => $validated['device_type'] ?? 'android',
                'device_name' => $validated['device_name'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Token perangkat berhasil didaftarkan.',
        ]);
    }

    /**
     * Simulate / Trigger a test notification for the authenticated user.
     */
    public function simulate(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'type' => 'required|string|in:approval,cuti,attendance,task,ess,announcement',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:500',
            'link' => 'nullable|string|max:200',
        ]);

        $defaultLinks = [
            'approval' => '/approvals',
            'cuti' => '/cuti-filter',
            'attendance' => '/absensi-history',
            'task' => '/tasks',
            'ess' => '/ess',
            'announcement' => '/dashboard',
        ];

        $link = $validated['link'] ?? ($defaultLinks[$validated['type']] ?? '/dashboard');
        $notifId = (string) \Illuminate\Support\Str::uuid();

        \DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'App\\Notifications\\' . ucfirst($validated['type']) . 'Notification',
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'type' => $validated['type'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'link' => $link,
                'is_simulation' => true,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi uji coba berhasil dikirim ke akun Anda.',
            'data' => [
                'id' => $notifId,
                'type' => $validated['type'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'link' => $link,
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ], 201);
    }
}
