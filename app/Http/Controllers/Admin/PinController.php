<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinController extends Controller
{
    /**
     * Check if user has PIN set.
     */
    public function checkStatus()
    {
        return response()->json([
            'has_pin' => !is_null(Auth::user()->pin)
        ]);
    }

    /**
     * Set PIN for the first time.
     */
    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:6|confirmed',
        ]);

        $user = Auth::user();
        
        if ($user->pin) {
            return response()->json(['message' => 'PIN already set. Use change PIN.'], 400);
        }

        $user->pin = Hash::make($request->pin);
        $user->must_change_pin = false;
        $user->save();

        activity()
            ->causedBy($user)
            ->log('PIN Keamanan pertama kali diatur');

        return response()->json(['message' => 'PIN berhasil dibuat.']);
    }

    /**
     * Change existing PIN.
     */
    public function changePin(Request $request)
    {
        $request->validate([
            'current_pin' => 'required',
            'pin' => 'required|digits:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_pin, $user->pin)) {
            throw ValidationException::withMessages([
                'current_pin' => ['PIN lama salah.'],
            ]);
        }

        $user->pin = Hash::make($request->pin);
        $user->must_change_pin = false;
        $user->save();

        activity()
            ->causedBy($user)
            ->log('PIN Keamanan telah diubah');

        return response()->json(['message' => 'PIN berhasil diubah.']);
    }

    /**
     * Reset forgotten PIN using authenticated user's account password.
     */
    public function resetPin(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'pin' => 'required|digits:6|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password akun yang Anda masukkan salah.'],
            ]);
        }

        $user->pin = Hash::make($request->pin);
        $user->must_change_pin = false;
        $user->save();

        \Illuminate\Support\Facades\RateLimiter::clear('pin-verify:' . $user->id);

        activity()
            ->causedBy($user)
            ->log('PIN Keamanan direset menggunakan verifikasi password akun');

        return response()->json(['message' => 'PIN keamanan berhasil direset.']);
    }

    /**
     * Verify PIN for actions (Approval).
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required',
        ]);

        app(\App\Services\PinVerificationService::class)->verifyAndRecord(
            $request,
            Auth::user(),
            (string) $request->input('pin')
        );

        return response()->json([
            'message' => 'PIN terverifikasi.',
            'has_pin' => true,
        ]);
    }
}
