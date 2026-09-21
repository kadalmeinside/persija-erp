<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // Use Hash if PIN is hashed, or direct compare if plain (assuming plain for now based on request "input satu satu", but better to hash. Plan said "string 6 chars". Let's assume plain for simplicity first or hash if user wants security. Usually PINs are hashed. Let's hash it for security best practice, but user might set it manually in DB as plain text first. Let's support plain text comparison for now as per "manual set via tinker" plan, but ideally should be hashed. Wait, standard Laravel Auth uses Hash. Let's assume the user stores it as plain text for this "manual" phase or hashed? The plan said "User will set PIN via DB/Tinker". If they set '123456' in DB, it's plain. If they use bcrypt('123456'), it's hashed. Let's assume PLAIN TEXT for simplicity of manual setting, or check both. Actually, for security, let's assume PLAIN TEXT in DB for this specific "manual setup" phase to avoid confusion, OR just simple string comparison.
// UPDATE: To make it robust, let's assume it's stored as a string. If we want to hash later we can. For now, direct comparison.

class PinVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user->pin) {
            return response()->json(['message' => 'PIN belum diatur. Hubungi administrator.'], 400);
        }

        // Verify PIN using Hash
        if (!Hash::check($request->pin, $user->pin)) {
            return response()->json(['message' => 'PIN salah.'], 422);
        }

        // Set Session (5 minutes)
        $request->session()->put('approval_pin_verified_at', now());

        return response()->json(['message' => 'PIN terverifikasi.']);
    }
}
