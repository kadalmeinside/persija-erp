<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\Ticket;
use App\Enums\Role;

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
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id'          => $request->user()->id,
                    'name'        => $request->user()->name,
                    'email'       => $request->user()->email,
                    'roles'       => $request->user()->getRoleNames(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
                    'karyawan'    => $request->user()->karyawan,
                ] : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'notifications' => function () use ($request) {
                if (!$request->user()) {
                    return ['list' => [], 'count' => 0];
                }
                // Satu query saja — gunakan count() dari collection yang sudah di-fetch
                $unread = $request->user()->unreadNotifications()->take(10)->get();
                return [
                    'list'  => $unread,
                    'count' => $unread->count(), // ← gunakan collection, bukan query kedua
                ];
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'type'    => fn () => $request->session()->get('type'),
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
            'app_settings' => function () {
                return Cache::rememberForever('app_settings', function () {
                    return Setting::all()->pluck('value', 'key');
                });
            },
            'open_tickets_count' => function () use ($request) {
                if ($request->user() && $request->user()->hasRole(Role::itRoles())) {
                    return Ticket::whereNotIn('status', ['Resolved', 'Closed'])->count();
                }
                return 0;
            },
            'is_cuti_approver' => function () use ($request) {
                if (!$request->user() || !$request->user()->karyawan) return false;
                $karyawanId = $request->user()->karyawan->id;
                $hasRule = \App\Models\ApprovalRule::where('tipe', 'Cuti')->where('id_karyawan_approver', $karyawanId)->exists();
                $hasPending = \App\Models\ApprovalProcess::whereNotNull('id_cuti')->where('id_karyawan_target', $karyawanId)->where('status', 'Pending')->exists();
                return $hasRule || $hasPending;
            },
            'is_pengajuan_approver' => function () use ($request) {
                if (!$request->user() || !$request->user()->karyawan) return false;
                $karyawanId = $request->user()->karyawan->id;
                // Pengajuan and Pinjaman and Invoice rules are considered 'pengajuan' approvers
                $hasRule = \App\Models\ApprovalRule::whereIn('tipe', ['Pengajuan', 'Pinjaman', 'Invoice'])->where('id_karyawan_approver', $karyawanId)->exists();
                $hasPending = \App\Models\ApprovalProcess::where(function($q) {
                    $q->whereNotNull('id_pengajuan')->orWhereNotNull('id_pinjaman')->orWhereNotNull('id_invoice');
                })->where('id_karyawan_target', $karyawanId)->where('status', 'Pending')->exists();
                return $hasRule || $hasPending;
            },
        ]);
    }
}