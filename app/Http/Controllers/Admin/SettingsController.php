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

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
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

        $fileKeys = ['app_logo', 'company_logo'];
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
}
