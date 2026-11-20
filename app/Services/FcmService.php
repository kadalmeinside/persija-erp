<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(
        private readonly SystemConfigService $configService
    ) {}

    /**
     * Send a push notification via Firebase Cloud Messaging HTTP v1 API.
     *
     * @param  array<string, mixed>  $data
     * @return array{sent: int, failed: int, skipped: bool, reason?: string}
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $fcm = $this->configService->getFcmConfig();

        if (!$fcm['enabled'] || !$fcm['project_id'] || !$fcm['service_account']) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => true,
                'reason' => 'FCM belum diaktifkan atau service account belum dikonfigurasi.',
            ];
        }

        $type = (string) ($data['type'] ?? 'general');
        $preference = NotificationPreference::where('user_id', $user->id)->first();
        if ($preference && !$this->isTypeAllowed($preference, $type)) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => true,
                'reason' => 'Dinonaktifkan oleh preferensi notifikasi pengguna.',
            ];
        }

        $tokens = UserDeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($tokens->isEmpty()) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => true,
                'reason' => 'Pengguna belum memiliki device token mobile aktif.',
            ];
        }

        $accessToken = $this->getAccessToken($fcm['service_account']);
        if (!$accessToken) {
            return [
                'sent' => 0,
                'failed' => $tokens->count(),
                'skipped' => false,
                'reason' => 'Gagal memperoleh OAuth2 access token dari Google Firebase.',
            ];
        }

        $stringData = [];
        foreach ($data as $key => $val) {
            if ($val !== null && is_scalar($val)) {
                $stringData[(string) $key] = (string) $val;
            }
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$fcm['project_id']}/messages:send";
        $sent = 0;
        $failed = 0;

        foreach ($tokens as $deviceToken) {
            try {
                $response = Http::timeout(10)
                    ->withToken($accessToken)
                    ->post($endpoint, [
                        'message' => [
                            'token' => $deviceToken->device_token,
                            'notification' => [
                                'title' => $title,
                                'body' => $body,
                            ],
                            'data' => $stringData,
                            'android' => [
                                'priority' => 'high',
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    $sent++;
                    $deviceToken->update(['last_used_at' => now()]);
                } else {
                    $failed++;
                    $responseBody = $response->body();
                    if (
                        str_contains($responseBody, 'UNREGISTERED')
                        || str_contains($responseBody, 'REGISTRATION_TOKEN_NOT_REGISTERED')
                    ) {
                        $deviceToken->update(['is_active' => false]);
                    }
                    Log::warning('FCM v1 send failed', [
                        'user_id' => $user->id,
                        'status' => $response->status(),
                        'body' => $responseBody,
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('FCM v1 HTTP exception', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => false,
        ];
    }

    /**
     * Send push notification to multiple users and aggregate delivery metrics.
     *
     * @param  iterable<int, User>  $users
     * @param  array<string, mixed>  $data
     * @return array{recipients: int, sent: int, failed: int, skipped: int, reason: ?string}
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): array
    {
        $recipients = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $lastReason = null;

        foreach ($users as $user) {
            if (!$user instanceof User) {
                continue;
            }
            $recipients++;
            $res = $this->sendToUser($user, $title, $body, $data);
            $sent += $res['sent'];
            $failed += $res['failed'];
            if (!empty($res['skipped'])) {
                $skipped++;
            }
            if (!empty($res['reason'])) {
                $lastReason = (string) $res['reason'];
            }
        }

        return [
            'recipients' => $recipients,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'reason' => $lastReason,
        ];
    }

    /**
     * Generate or return cached OAuth2 access token for FCM HTTP v1 using JWT RS256.
     *
     * @param  array<string, mixed>  $serviceAccount
     */
    public function getAccessToken(array $serviceAccount): ?string
    {
        $cached = Cache::get('fcm_oauth_access_token');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $clientEmail = $serviceAccount['client_email'] ?? null;
        $privateKey = $serviceAccount['private_key'] ?? null;

        if (!$clientEmail || !$privateKey) {
            return null;
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]) ?: '{}');

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]) ?: '{}');

        $unsigned = "{$header}.{$claims}";
        $signature = '';
        $signed = @openssl_sign($unsigned, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            Log::error('FCM JWT openssl_sign gagal memproses private key service account.');
            return null;
        }

        $jwt = $unsigned . '.' . $this->base64UrlEncode($signature);

        try {
            $response = Http::timeout(10)
                ->asForm()
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                if (is_string($token) && $token !== '') {
                    Cache::put('fcm_oauth_access_token', $token, 3000);
                    return $token;
                }
            }

            Log::warning('FCM OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('FCM OAuth token exchange exception: ' . $e->getMessage());
        }

        return null;
    }

    private function isTypeAllowed(NotificationPreference $preference, string $type): bool
    {
        return match ($type) {
            'approval' => (bool) $preference->approval_notifications,
            'cuti' => (bool) $preference->leave_notifications,
            'attendance' => (bool) $preference->attendance_notifications,
            'task' => (bool) $preference->task_notifications,
            'announcement' => (bool) $preference->announcement_notifications,
            default => true,
        };
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
