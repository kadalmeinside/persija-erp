<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User; // Contoh jika perlu model User

class UserController extends Controller
{
    // Hanya user dengan role 'admin' atau permission 'manage users' yang bisa akses method ini
    public function __construct()
    {
        // $this->middleware('role:admin'); // Middleware di constructor
        // atau
        // $this->middleware('permission:manage users');
        // atau kombinasi
        // $this->middleware(['role:admin', 'permission:manage users']);
    }

    public function index()
    {
        // Otorisasi di dalam method (jika tidak pakai middleware di route/constructor)
        if (!auth()->user()->hasPermissionTo('manage users')) {
            abort(403, 'ANDA TIDAK PUNYA AKSES');
        }

        // atau gunakan $this->authorize()
        // $this->authorize('manage users'); // Akan throw AuthorizationException jika gagal

        $users = User::all(); // Ambil data user
        // Tampilkan view atau return data
        // return Inertia::render('Admin/Users', ['users' => $users]);
        return "Halaman User Management (Khusus Admin)";
    }

    public function destroy(User $user)
    {
        $this->authorize('manage users'); // Pastikan punya izin

        // Logika hapus user
        $user->delete();

        return redirect()->back()->with('message', 'User berhasil dihapus');
    }
}