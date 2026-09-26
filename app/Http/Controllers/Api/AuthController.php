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
}
