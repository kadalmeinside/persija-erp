<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class EssSubmissionNotification extends Notification
{
    public string $moduleType; // 'attendance', 'cuti', 'ess'
    public string $title;
    public string $message;
    public string $link;

    public function __construct(string $moduleType, string $title, string $message, string $link = '/ess')
    {
        $this->moduleType = $moduleType;
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->moduleType,
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
        ];
    }
}
