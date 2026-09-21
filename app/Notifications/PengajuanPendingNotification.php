<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengajuanPendingNotification extends Notification
{
    use Queueable;

    public $pengajuan;

    /**
     * Create a new notification instance.
     */
    public function __construct($pengajuan = null)
    {
        $this->pengajuan = $pengajuan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Pengajuan Baru Menunggu Approval',
            'id' => $this->pengajuan ? $this->pengajuan->id : null,
            'type' => $this->pengajuan ? class_basename($this->pengajuan) : 'General',
            'link' => route('admin.pengajuan.index', ['status' => \App\Enums\PengajuanStatus::PENDING_APPROVAL->value]),
        ];
    }
}
