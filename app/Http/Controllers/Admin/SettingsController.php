<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SettingsController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('settings.manage');

        $rawSettings = Setting::all()->pluck('value', 'key')->toArray();
        $settings = $rawSettings;
        $settings['has_mail_password'] = !empty($rawSettings['mail_password']);
        $settings['has_fcm_service_account'] = !empty($rawSettings['fcm_service_account_json']);
        unset($settings['mail_password'], $settings['fcm_service_account_json']);

        $users = \App\Models\User::select('id', 'name', 'email')
            ->with(['roles:id,name', 'karyawan:id,user_id,jabatan'])
            ->get()
            ->map(function ($u) {
                $latestToken = \App\Models\UserDeviceToken::where('user_id', $u->id)
                    ->where('is_active', true)
                    ->latest()
                    ->first();

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->roles->first()?->name ?? 'Staff',
                    'jabatan' => $u->karyawan?->jabatan ?? '-',
                    'has_device_token' => $latestToken !== null,
                    'device_name' => $latestToken?->device_name ?? null,
                    'device_type' => $latestToken?->device_type ?? null,
                ];
            });

        $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'users' => $users,
            'roles' => $roles,
            'currentUserId' => auth()->id(),
            'pageTitle' => 'Pengaturan Aplikasi',
            'can' => [
                'update_settings' => auth()->user()->can('settings.manage'),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|max:1024',
            'app_favicon' => 'nullable|image|mimes:png,ico|max:512',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_logo' => 'nullable|image|max:2048',
            'reimburse_tolerance_limit' => 'nullable|numeric|min:0',
            'mail_enabled' => 'nullable|boolean',
            'mail_mailer' => 'nullable|string|in:smtp,log',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:500',
            'mail_encryption' => 'nullable|string|in:tls,ssl,none',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'fcm_enabled' => 'nullable|boolean',
            'fcm_project_id' => 'nullable|string|max:255',
            'fcm_service_account_json' => 'nullable|string|max:20000',
        ]);

        $configService = app(\App\Services\SystemConfigService::class);

        $keys = [
            'app_name',
            'company_name',
            'company_address',
            'reimburse_tolerance_limit',
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
            'fcm_project_id',
        ];

        foreach ($keys as $key) {
            if ($request->exists($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        foreach (['mail_enabled', 'fcm_enabled'] as $boolKey) {
            if ($request->exists($boolKey)) {
                Setting::updateOrCreate(
                    ['key' => $boolKey],
                    ['value' => $request->boolean($boolKey) ? '1' : '0']
                );
            }
        }

        if ($request->filled('mail_password')) {
            Setting::updateOrCreate(
                ['key' => 'mail_password'],
                ['value' => $configService->encryptValue((string) $request->input('mail_password'))]
            );
        }

        if ($request->filled('fcm_service_account_json')) {
            $rawJson = trim((string) $request->input('fcm_service_account_json'));
            $decoded = json_decode($rawJson, true);
            if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key']) || empty($decoded['project_id'])) {
                return Redirect::back()->withErrors([
                    'fcm_service_account_json' => 'Service Account JSON Firebase tidak valid (wajib memuat project_id, client_email, dan private_key).',
                ])->withInput();
            }

            Setting::updateOrCreate(
                ['key' => 'fcm_service_account_json'],
                ['value' => $configService->encryptValue($rawJson)]
            );

            if (empty($validated['fcm_project_id']) && !empty($decoded['project_id'])) {
                Setting::updateOrCreate(
                    ['key' => 'fcm_project_id'],
                    ['value' => (string) $decoded['project_id']]
                );
            }
        }

        $fileKeys = ['app_logo', 'app_favicon', 'company_logo'];
        foreach ($fileKeys as $key) {
            if ($request->hasFile($key)) {
                $oldPath = Setting::where('key', $key)->value('value');
                if ($oldPath) {
                    Storage::disk('public')->delete($oldPath);
                }

                $path = $request->file($key)->store('logos', 'public');
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $path]
                );
            }
        }

        $configService->clearCache();

        return Redirect::route('admin.settings.index')->with([
            'message' => 'Pengaturan berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    /**
     * Send test SMTP email using database-configured mail settings.
     */
    public function sendTestEmail(Request $request)
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        try {
            $configService = app(\App\Services\SystemConfigService::class);
            $configService->applyMailConfig();

            \Illuminate\Support\Facades\Mail::raw(
                'Ini adalah email uji coba dari pengaturan SMTP Persija ERP Web Settings. Konfigurasi SMTP berhasil terhubung.',
                function ($message) use ($validated) {
                    $message->to($validated['test_email'])
                        ->subject('[Persija ERP] Uji Coba Konfigurasi Email SMTP');
                }
            );

            return Redirect::back()->with([
                'message' => "Email uji coba berhasil dikirim ke {$validated['test_email']}.",
                'type' => 'success',
            ]);
        } catch (\Throwable $e) {
            return Redirect::back()->with([
                'message' => 'Gagal mengirim email uji coba: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
    }

    /**
     * Send push notification / broadcast message from Web Settings to single user, multi-user, role, or all users.
     */
    public function sendTestNotification(Request $request)
    {
        $this->authorize('settings.manage');

        $targetMode = (string) $request->input('target_mode', 'single_user');
        $clickAction = (string) $request->input('click_action', 'open_page');

        $validated = $request->validate([
            'target_mode' => 'nullable|string|in:single_user,multi_user,by_role,blast_all',
            'target_user_id' => [
                \Illuminate\Validation\Rule::requiredIf($targetMode === 'single_user'),
                'nullable',
                'exists:users,id',
            ],
            'target_user_ids' => [
                \Illuminate\Validation\Rule::requiredIf($targetMode === 'multi_user'),
                'nullable',
                'array',
                'min:1',
            ],
            'target_user_ids.*' => 'exists:users,id',
            'target_role' => [
                \Illuminate\Validation\Rule::requiredIf($targetMode === 'by_role'),
                'nullable',
                'string',
                'exists:roles,name',
            ],
            'type' => 'required|string|in:approval,cuti,attendance,task,ess,announcement,custom',
            'click_action' => 'nullable|string|in:open_page,info_only',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:1000',
            'link' => 'nullable|string|max:200',
        ]);

        $recipients = match ($targetMode) {
            'blast_all' => \App\Models\User::all(),
            'by_role' => \App\Models\User::role($validated['target_role'])->get(),
            'multi_user' => \App\Models\User::whereIn('id', $validated['target_user_ids'] ?? [])->get(),
            default => \App\Models\User::where('id', $validated['target_user_id'])->get(),
        };

        if ($recipients->isEmpty()) {
            return Redirect::back()->with([
                'message' => 'Tidak ada pengguna penerima yang ditemukan untuk target ini.',
                'type' => 'error',
            ]);
        }

        $defaultLinks = [
            'approval' => '/approvals',
            'cuti' => '/cuti-filter',
            'attendance' => '/absensi-history',
            'task' => '/tasks',
            'ess' => '/ess?tab=payslip',
            'announcement' => '/dashboard',
            'custom' => '/dashboard',
        ];

        $link = $clickAction === 'info_only'
            ? null
            : ($validated['link'] ?: ($defaultLinks[$validated['type']] ?? '/dashboard'));

        $batchId = (string) \Illuminate\Support\Str::uuid();
        $now = now();
        $rows = [];

        foreach ($recipients as $recipient) {
            $rows[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\' . ucfirst($validated['type']) . 'Notification',
                'notifiable_type' => get_class($recipient),
                'notifiable_id' => $recipient->id,
                'data' => json_encode([
                    'batch_id' => $batchId,
                    'type' => $validated['type'],
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                    'link' => $link,
                    'click_action' => $clickAction,
                    'target_mode' => $targetMode,
                    'sent_from' => 'Web Settings ERP',
                    'sent_by' => auth()->user()?->name ?? 'System',
                ]),
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            \DB::table('notifications')->insert($chunk);
        }

        $fcmResult = app(\App\Services\FcmService::class)->sendToUsers(
            $recipients,
            $validated['title'],
            $validated['message'],
            [
                'batch_id' => $batchId,
                'type' => $validated['type'],
                'link' => $link ?? '',
                'click_action' => $clickAction,
            ]
        );

        \App\Services\ImmutableAuditService::log(
            action: 'PUSH_NOTIFICATION_DISPATCHED',
            entityType: 'NotificationBroadcast',
            entityId: $batchId,
            payload: [
                'target_mode' => $targetMode,
                'type' => $validated['type'],
                'click_action' => $clickAction,
                'link' => $link,
                'recipients_count' => $recipients->count(),
                'fcm_sent' => $fcmResult['sent'],
            ],
            actorId: auth()->id(),
            actorName: auth()->user()?->name,
        );

        $recipientSummary = $recipients->count() === 1
            ? $recipients->first()->name
            : $recipients->count() . ' pengguna';

        $fcmNote = ($fcmResult['sent'] ?? 0) > 0
            ? " (FCM push terkirim ke {$fcmResult['sent']} perangkat)."
            : (!empty($fcmResult['reason']) ? " (In-app tersimpan; FCM: {$fcmResult['reason']})" : '');

        return Redirect::back()->with([
            'message' => "Push message '{$validated['title']}' berhasil dikirim ke {$recipientSummary}{$fcmNote}",
            'type' => 'success',
        ]);
    }
}
