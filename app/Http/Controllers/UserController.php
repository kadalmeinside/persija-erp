<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
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
        if (!auth()->user()->hasPermissionTo('user.manage')) {
            abort(403, 'ANDA TIDAK PUNYA AKSES');
        }


        $users = User::all(); 
        return "Halaman User Management (Khusus Admin)";
    }

    public function destroy(User $user)
    {
        $this->authorize('manage users'); 

        $user->delete();

        return redirect()->back()->with('message', 'User berhasil dihapus');
    }
}