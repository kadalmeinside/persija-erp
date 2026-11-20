<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCredentialsRotated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(
            'profile.edit',
            'profile.update',
            'password.update',
            'logout',
            'admin.pin.*',
            'admin.auth.verify-pin'
        )) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Anda wajib mengganti password sementara sebelum menggunakan sistem.',
                'requires_credential_rotation' => true,
            ], 403);
        }

        return redirect()
            ->route('profile.edit')
            ->with('warning', 'Demi keamanan, Anda wajib mengganti password sementara sebelum melanjutkan.');
    }
}
