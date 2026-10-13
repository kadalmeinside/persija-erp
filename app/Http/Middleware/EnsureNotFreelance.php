<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\Role;

class EnsureNotFreelance
{
    /**
     * Handle an incoming request.
     *
     * Membatasi agar akun dengan role 'Freelance' hanya bisa mengakses modul Absensi.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole(Role::FREELANCE->value)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Role Freelance hanya memiliki akses untuk modul Absensi.',
                ], 403);
            }

            return redirect()->route('admin.absensi.clock')->with('error', 'Akses ditolak. Akun Freelance hanya dapat mengakses modul Absensi.');
        }

        return $next($request);
    }
}
