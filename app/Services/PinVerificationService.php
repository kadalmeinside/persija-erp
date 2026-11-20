<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PinVerificationService
{
    public const MAX_ATTEMPTS = 5;
    public const LOCKOUT_SECONDS = 900; // 15 minutes
    public const SESSION_TTL_MINUTES = 5;

    /**
     * Verify a raw 6-digit PIN with brute-force lockout protection and record session.
     */
    public function verifyAndRecord(Request $request, User $user, string $pin): void
    {
        $key = $this->throttleKey($user);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = (int) ceil($seconds / 60);
            $message = "Terlalu banyak percobaan PIN gagal. Akun PIN terkunci selama {$minutes} menit.";

            if ($request->expectsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => $message,
                    'retry_after' => $seconds,
                ], 429));
            }

            throw ValidationException::withMessages(['pin' => [$message]]);
        }

        if (!$user->pin) {
            if ($request->expectsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Anda belum memiliki PIN.',
                    'has_pin' => false,
                ], 400));
            }

            throw ValidationException::withMessages(['pin' => ['Anda belum mengatur PIN keamanan.']]);
        }

        if (!Hash::check($pin, $user->pin)) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);
            $remaining = max(0, self::MAX_ATTEMPTS - RateLimiter::attempts($key));
            $message = $remaining > 0
                ? "PIN salah. Sisa percobaan: {$remaining}."
                : 'PIN salah. Terlalu banyak percobaan gagal, PIN terkunci sementara.';

            if ($request->expectsJson()) {
                throw new HttpResponseException(response()->json([
                    'message' => $message,
                    'has_pin' => true,
                ], 403));
            }

            throw ValidationException::withMessages(['pin' => [$message]]);
        }

        RateLimiter::clear($key);

        if ($request->hasSession()) {
            $request->session()->put('approval_pin_verified_at', now()->toIso8601String());
        }
    }

    /**
     * Check whether the current request has a valid 5-minute PIN session.
     */
    public function hasValidSession(Request $request): bool
    {
        if (!$request->hasSession()) {
            return false;
        }

        $verifiedAt = $request->session()->get('approval_pin_verified_at');
        if (!$verifiedAt) {
            return false;
        }

        return Carbon::parse($verifiedAt)->gte(now()->subMinutes(self::SESSION_TTL_MINUTES));
    }

    /**
     * Assert that either a valid PIN is provided in the request or an active 5-minute PIN session exists.
     */
    public function assertVerified(Request $request, User $user): void
    {
        if ($request->filled('pin')) {
            $this->verifyAndRecord($request, $user, (string) $request->input('pin'));
            return;
        }

        if (!$this->hasValidSession($request)) {
            throw ValidationException::withMessages([
                'pin' => ['Verifikasi PIN diperlukan sebelum memproses persetujuan.'],
            ]);
        }
    }

    private function throttleKey(User $user): string
    {
        return 'pin-verify:' . $user->id;
    }
}
