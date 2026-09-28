<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Handle user login and issue a Sanctum token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // Revoke older tokens to ensure clean session (optional based on policy, but good for mobile)
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('mobile-app')->plainTextToken;

        $user->load('karyawan.lokasiKantor');

        return response()->json([
            'data' => [
                'token' => $token,
                'user' => new \App\Http\Resources\UserResource($user)
            ]
        ], 200); // Or 201 if you consider token creation
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request)
    {
        // Load relation for resource if needed, otherwise UserResource will handle it
        $user = $request->user()->load('karyawan');
        
        return new \App\Http\Resources\UserResource($user);
    }

    /**
     * Logout user by revoking token.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed', // Must match new_password_confirmation
        ]);

        $user = $request->user();

        // Cek apakah password lama sesuai
        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password lama yang Anda masukkan salah.'
            ], 422);
        }

        // Update password baru
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.'
        ], 200);
    }
}
