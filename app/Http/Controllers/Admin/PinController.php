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
        $user->save();

        activity()
            ->causedBy($user)
            ->log('PIN Keamanan telah diubah');

        return response()->json(['message' => 'PIN berhasil diubah.']);
    }

    /**
     * Verify PIN for actions (Approval).
     */
    public function verifyPin(Request $request)
    {
        $request->validate([
            'pin' => 'required',
        ]);

        $user = Auth::user();

        if (!$user->pin) {
            return response()->json(['message' => 'PIN belum diset.'], 400);
        }

        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['message' => 'PIN salah.'], 403);
        }

        return response()->json(['message' => 'PIN valid.']);
    }
}
