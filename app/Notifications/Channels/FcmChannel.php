<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\FcmService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    public function __construct(
        private readonly FcmService $fcmService
    ) {}

    /**
     * Send the given notification via Firebase Cloud Messaging HTTP v1.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (!$notifiable instanceof User) {
            return;
        }

        if (!method_exists($notification, 'toArray')) {
            return;
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = $notification->toArray($notifiable);
            $title = (string) ($payload['title'] ?? 'Persija ERP');
            $body = (string) ($payload['message'] ?? '');

            $this->fcmService->sendToUser($notifiable, $title, $body, $payload);
        } catch (\Throwable $e) {
            Log::warning('FcmChannel gagal mengirim notifikasi', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
