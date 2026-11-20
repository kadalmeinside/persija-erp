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

        $user->load(['karyawan.lokasiKantor', 'karyawan.biometric']);

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
        $user = $request->user()->load(['karyawan.lokasiKantor', 'karyawan.biometric']);
        
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

        // Update password baru & bersihkan flag rotasi kredensial awal
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->new_password),
            'must_change_password' => false,
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.'
        ], 200);
    }

    /**
     * Send one-time temporary password via dynamic DB SMTP configuration.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $configService = app(\App\Services\SystemConfigService::class);
        if (!$configService->isMailConfigured()) {
            return response()->json([
                'message' => 'Layanan email SMTP belum diaktifkan. Silakan hubungi HR atau IT Support untuk reset password.',
            ], 503);
        }

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user) {
            $tempPassword = \Illuminate\Support\Str::random(10);

            \Illuminate\Support\Facades\DB::beginTransaction();
            try {
                $user->update([
                    'password' => \Illuminate\Support\Facades\Hash::make($tempPassword),
                    'must_change_password' => true,
                ]);

                $configService->applyMailConfig();

                \Illuminate\Support\Facades\Mail::raw(
                    "Halo {$user->name},\n\nAnda atau admin meminta reset password akun Persija ERP.\nPassword sementara Anda: {$tempPassword}\n\nDemi keamanan, Anda diwajibkan segera mengganti password setelah berhasil login.\nJika Anda tidak merasa meminta reset ini, segera hubungi HR atau IT Support.",
                    function ($message) use ($user) {
                        $message->to($user->email)
                            ->subject('[Persija ERP] Reset Password Sementara Akun Anda');
                    }
                );

                \Illuminate\Support\Facades\DB::commit();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\DB::rollBack();

                return response()->json([
                    'message' => 'Gagal mengirim email reset password. Periksa konfigurasi SMTP atau hubungi IT Support.',
                ], 503);
            }
        }

        return response()->json([
            'message' => 'Jika email terdaftar, password sementara telah dikirim ke inbox email Anda.',
        ], 200);
    }

    /**
     * Register face descriptor for the authenticated user.
     */
    public function registerFace(Request $request)
    {
        $request->validate([
            'face_descriptor' => 'required|string',
        ]);

        $user = $request->user();
        if (!$user->karyawan) {
            return response()->json(['message' => 'Data karyawan tidak ditemukan.'], 404);
        }

        $biometric = \App\Models\KaryawanBiometric::firstOrCreate(
            ['id_karyawan' => $user->karyawan->id]
        );
        $biometric->setDescriptor('mobile', $request->face_descriptor);

        $user->karyawan->unsetRelation('biometric');

        return response()->json([
            'message' => 'Wajah berhasil didaftarkan.',
            'user' => new \App\Http\Resources\UserResource($user->load(['karyawan.lokasiKantor', 'karyawan.biometric'])),
        ], 200);
    }
}
