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
        $this->authorize('settings.manage'); // Gunakan permission
        // if (!$request->user()->can('manage application settings')) {
        //     abort(403);
        // }

        $settings = Setting::all()->pluck('value', 'key');

        $users = \App\Models\User::select('id', 'name', 'email')
            ->with(['roles:id,name', 'karyawan:id,user_id,jabatan,departemen'])
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

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'users' => $users,
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
        // if (!$request->user()->can('manage application settings')) {
        //     abort(403);
        // }

        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|max:1024', // Max 1MB
            'app_favicon' => 'nullable|image|mimes:png,ico|max:512', // Max 512KB for Favicon
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_logo' => 'nullable|image|max:2048', // Max 2MB
            'reimburse_tolerance_limit' => 'nullable|numeric|min:0',
        ]);

        $keys = ['app_name', 'company_name', 'company_address', 'reimburse_tolerance_limit'];
        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
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

        Cache::forget('app_settings');

        return Redirect::route('admin.settings.index')->with([
            'message' => 'Pengaturan berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    /**
     * Send test push notification from Web Settings to selected mobile user.
     */
    public function sendTestNotification(Request $request)
    {
        $this->authorize('settings.manage');

        $validated = $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:approval,cuti,attendance,task,ess,announcement,custom',
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:500',
            'link' => 'nullable|string|max:200',
        ]);

        $targetUser = \App\Models\User::findOrFail($validated['target_user_id']);
        $defaultLinks = [
            'approval' => '/approvals',
            'cuti' => '/cuti-filter',
            'attendance' => '/absensi-history',
            'task' => '/tasks',
            'ess' => '/ess?tab=payslip',
            'announcement' => '/dashboard',
            'custom' => '/dashboard',
        ];

        $link = $validated['link'] ?: ($defaultLinks[$validated['type']] ?? '/dashboard');
        $notifId = (string) \Illuminate\Support\Str::uuid();

        \DB::table('notifications')->insert([
            'id' => $notifId,
            'type' => 'App\\Notifications\\' . ucfirst($validated['type']) . 'Notification',
            'notifiable_type' => get_class($targetUser),
            'notifiable_id' => $targetUser->id,
            'data' => json_encode([
                'type' => $validated['type'],
                'title' => $validated['title'],
                'message' => $validated['message'],
                'link' => $link,
                'is_simulation' => true,
                'sent_from' => 'Web Settings ERP',
                'sent_by' => auth()->user()->name,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Redirect::back()->with([
            'message' => "Notifikasi '{$validated['title']}' berhasil dikirim ke {$targetUser->name}!",
            'type' => 'success',
        ]);
    }
}
