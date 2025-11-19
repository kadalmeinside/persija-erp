<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // --- TAMBAHAN 2: BLOK DEBUGGING ---
        // Kita akan log data SEBELUM dikirim ke Inertia
        if ($request->user()) {
            Log::info('====================================');
            Log::info('DEBUGGING HandleInertiaRequests:');
            Log::info('User: ' . $request->user()->email);
            Log::info('Roles dari Spatie:', $request->user()->getRoleNames()->toArray());
            Log::info('Permissions dari Spatie:', $request->user()->getAllPermissions()->pluck('name')->toArray());
            Log::info('====================================');
        }
        // --- AKHIR BLOK DEBUGGING ---

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'roles' => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                // 'message' & 'type' yang sudah Anda miliki
                'message' => fn () => $request->session()->get('message'),
                'type' => fn () => $request->session()->get('type'),
                
                // Tambahkan ini untuk error & success
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app_settings' => function () {
                return Cache::rememberForever('app_settings', function () {
                    return Setting::all()->pluck('value', 'key');
                });
            },
        ]);
    }
}