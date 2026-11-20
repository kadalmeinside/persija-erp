<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class SystemConfigService
{
    /**
     * Retrieve all application settings from DB with cache.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        try {
            return Cache::rememberForever('app_settings', function () {
                return Setting::all()->pluck('value', 'key')->toArray();
            });
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Clear cached application settings and OAuth tokens.
     */
    public function clearCache(): void
    {
        Cache::forget('app_settings');
        Cache::forget('fcm_oauth_access_token');
    }

    public function encryptValue(string $value): string
    {
        return Crypt::encryptString($value);
    }

    public function decryptValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * Apply database-managed SMTP settings at runtime when enabled.
     */
    public function applyMailConfig(): bool
    {
        $settings = $this->getSettings();
        $enabled = filter_var($settings['mail_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (!$enabled) {
            return false;
        }

        $mailer = $settings['mail_mailer'] ?? 'smtp';
        if (!in_array($mailer, ['smtp', 'log'], true)) {
            $mailer = 'smtp';
        }

        config(['mail.default' => $mailer]);

        if ($mailer === 'smtp' && !empty($settings['mail_host'])) {
            $encryption = $settings['mail_encryption'] ?? 'tls';
            $scheme = match ($encryption) {
                'ssl' => 'smtps',
                'tls' => 'smtp',
                default => null,
            };

            config([
                'mail.mailers.smtp.host' => $settings['mail_host'],
                'mail.mailers.smtp.port' => (int) ($settings['mail_port'] ?? 587),
                'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
                'mail.mailers.smtp.password' => $this->decryptValue($settings['mail_password'] ?? null),
                'mail.mailers.smtp.scheme' => $scheme,
            ]);
        }

        if (!empty($settings['mail_from_address'])) {
            config(['mail.from.address' => $settings['mail_from_address']]);
        }

        if (!empty($settings['mail_from_name'])) {
            config(['mail.from.name' => $settings['mail_from_name']]);
        }

        Mail::purge($mailer);

        return true;
    }

    /**
     * Determine whether an email transport is usable for outgoing mail.
     */
    public function isMailConfigured(): bool
    {
        $settings = $this->getSettings();
        $dbEnabled = filter_var($settings['mail_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($dbEnabled && (($settings['mail_mailer'] ?? 'smtp') !== 'smtp' || !empty($settings['mail_host']))) {
            return true;
        }

        $defaultMailer = (string) config('mail.default', 'log');
        if ($defaultMailer === 'smtp' && !empty(config('mail.mailers.smtp.host')) && config('mail.mailers.smtp.host') !== '127.0.0.1') {
            return true;
        }

        return app()->runningUnitTests() || in_array($defaultMailer, ['array', 'log'], true);
    }

    /**
     * Retrieve normalized FCM HTTP v1 configuration from DB or fallback credentials path.
     *
     * @return array{enabled: bool, project_id: ?string, service_account: ?array<string, mixed>}
     */
    public function getFcmConfig(): array
    {
        $settings = $this->getSettings();
        $enabled = filter_var($settings['fcm_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $rawJson = $this->decryptValue($settings['fcm_service_account_json'] ?? null);
        $serviceAccount = null;

        if ($rawJson) {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                $serviceAccount = $decoded;
            }
        }

        if ($serviceAccount === null) {
            $credPath = env('FIREBASE_CREDENTIALS');
            if ($credPath && is_file($credPath)) {
                $decoded = json_decode((string) file_get_contents($credPath), true);
                if (is_array($decoded) && !empty($decoded['client_email']) && !empty($decoded['private_key'])) {
                    $serviceAccount = $decoded;
                }
            }
        }

        $projectId = $settings['fcm_project_id'] ?? ($serviceAccount['project_id'] ?? null);

        return [
            'enabled' => $enabled && !empty($projectId) && $serviceAccount !== null,
            'project_id' => $projectId ? (string) $projectId : null,
            'service_account' => $serviceAccount,
        ];
    }
}
