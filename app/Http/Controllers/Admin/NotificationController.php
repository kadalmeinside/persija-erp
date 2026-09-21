<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Mark a specific notification as read and redirect to its link.
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        
        $notification->markAsRead();
        
        if (isset($notification->data['link'])) {
            return redirect($notification->data['link']);
        }
        
        return back();
    }
    
    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }
}
