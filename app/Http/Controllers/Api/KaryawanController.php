<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $karyawan = Karyawan::select('id', 'nama_lengkap', 'jabatan')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('status_karyawan')
                    ->orWhereNotIn('status_karyawan', ['Nonaktif', 'Resign', 'Terminated']);
            })
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data karyawan',
            'data' => $karyawan
        ]);
    }
}
