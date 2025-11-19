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
        ]);

        if ($request->has('app_name')) {
            Setting::updateOrCreate(
                ['key' => 'app_name'],
                ['value' => $validated['app_name']]
            );
        }

        if ($request->hasFile('app_logo')) {
            $oldLogoPath = Setting::where('key', 'app_logo')->value('value');
            if ($oldLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $path]
            );
        }

        Cache::forget('app_settings');

        return Redirect::route('admin.settings.index')->with([
            'message' => 'Pengaturan berhasil diperbarui.',
            'type' => 'success'
        ]);
    }
}
