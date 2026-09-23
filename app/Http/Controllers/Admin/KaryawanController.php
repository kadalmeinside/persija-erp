<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\User;
use App\Models\SaldoCuti;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        $query = Karyawan::with(['departemen', 'user', 'rekeningBank', 'latestRiwayatKarir']);

        if ($request->status_aktif == 'non-aktif') {
            $query->onlyTrashed();
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nama_lengkap', 'like', '%' . $request->search . '%')
                  ->orWhere('nomor_induk_karyawan', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->departemen) {
            $query->where('id_departemen', $request->departemen);
        }

        $karyawans = $query->orderBy('nama_lengkap')->paginate(10);
        
        // Append primary_bank to collection so frontend can access it
        $karyawans->getCollection()->transform(function ($karyawan) {
            $karyawan->append('primary_bank');
            return $karyawan;
        });
        
        $departemens = Departemen::orderBy('nama_departemen')->get();
        $lokasiKantors = \App\Models\LokasiKantor::where('is_active', true)->orderBy('nama_kantor')->get();

        return Inertia::render('Admin/Karyawan/Index', [
            'karyawans' => $karyawans,
            'departemens' => $departemens,
            'lokasiKantors' => $lokasiKantors,
            'filters' => $request->only(['search', 'departemen', 'status_aktif'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nomor_induk_karyawan' => ['required', 'string', 'max:100', Rule::unique('tbl_karyawan', 'nomor_induk_karyawan')->whereNull('deleted_at')],
            'id_departemen' => 'required|exists:tbl_departemen,id',
            'jenis_kelamin' => 'required|in:L,P',
            'jabatan' => 'required|string|max:100',
            'tgl_bergabung' => 'nullable|date',
            'status_karyawan' => 'required|in:Tetap,Kontrak,Magang',
            'email' => 'nullable|email|unique:users,email',
            'create_user' => 'boolean',
            'password' => 'nullable|string|min:8', // Optional password
            'role' => 'nullable|string|exists:roles,name', // Optional role
            'foto' => 'nullable|image|max:2048',
            'id_lokasi_kantor' => 'nullable|exists:tbl_lokasi_kantor,id',
            'is_strict_location' => 'boolean'
        ]);

        DB::transaction(function () use ($request) {
            $userId = null;

            if ($request->create_user && $request->email) {
                $password = $request->password ?? 'password123';
                $user = User::create([
                    'name' => $request->nama_lengkap,
                    'email' => $request->email,
                    'password' => Hash::make($password),
                ]);
                
                $role = $request->role ?? 'Staf';
                $user->assignRole($role);
                
                $userId = $user->id;
            }

            $karyawan = Karyawan::create([
                'user_id' => $userId,
                'id_departemen' => $request->id_departemen,
                'nomor_induk_karyawan' => $request->nomor_induk_karyawan,
                'nama_lengkap' => $request->nama_lengkap,
                'jenis_kelamin' => $request->jenis_kelamin,
                'jabatan' => $request->jabatan,
                'tgl_bergabung' => $request->tgl_bergabung,
                'status_karyawan' => $request->status_karyawan,
                'tempat_lahir' => $request->tempat_lahir,
                'tgl_lahir' => $request->tgl_lahir,
                'alamat' => $request->alamat,
                'gaji_pokok' => $request->gaji_pokok ?? 0,
                'status_ptkp' => $request->status_ptkp ?? 'TK/0',
                'id_lokasi_kantor' => $request->id_lokasi_kantor,
                'is_strict_location' => $request->is_strict_location ?? false,
            ]);

            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('karyawan_fotos', 'public');
                $karyawan->update(['foto' => $path]);
            }

            if ($request->nama_bank && $request->nomor_rekening) {
                $karyawan->rekeningBank()->create([
                    'nama_bank' => $request->nama_bank,
                    'nomor_rekening' => $request->nomor_rekening,
                    'atas_nama_rekening' => $request->atas_nama_rekening ?? $request->nama_lengkap,
                    'is_primary' => true
                ]);
            }
        });

        return redirect()->back()->with('success', 'Data Karyawan berhasil ditambahkan.');
    }

    public function show(Karyawan $karyawan)
    {
        $karyawan->load(['departemen', 'user', 'rekeningBank', 'riwayatKarir.departemen', 'lokasiKantor']);
        $karyawan->append('primary_bank');
        
        // Load Leave Balances
        $currentYear = date('Y');
        $leaveBalances = SaldoCuti::with('jenisCuti')
                            ->where('id_karyawan', $karyawan->id)
                            ->where('tahun_periode', $currentYear)
                            ->get();

        $departemens = \App\Models\Departemen::all(); // Needed for Edit Form

        return Inertia::render('Admin/Karyawan/Show', [
            'karyawan' => $karyawan,
            'leaveBalances' => $leaveBalances,
            'departemens' => $departemens
        ]);
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'nomor_induk_karyawan' => ['required', 'string', 'max:100', Rule::unique('tbl_karyawan')->ignore($karyawan->id)->whereNull('deleted_at')],
            'id_departemen' => 'required|exists:tbl_departemen,id',
            'jenis_kelamin' => 'required|in:L,P',
            'jabatan' => 'required|string|max:100',
            'status_karyawan' => 'required|in:Tetap,Kontrak,Magang',
            'foto' => 'nullable|image|max:2048',
            'id_lokasi_kantor' => 'nullable|exists:tbl_lokasi_kantor,id',
            'is_strict_location' => 'boolean'
        ]);

        $karyawan->update([
            'nama_lengkap' => $request->nama_lengkap,
            'nomor_induk_karyawan' => $request->nomor_induk_karyawan,
            'id_departemen' => $request->id_departemen,
            'jenis_kelamin' => $request->jenis_kelamin,
            'jabatan' => $request->jabatan,
            'tgl_bergabung' => $request->tgl_bergabung,
            'status_karyawan' => $request->status_karyawan,
            'tempat_lahir' => $request->tempat_lahir,
            'tgl_lahir' => $request->tgl_lahir,
            'alamat' => $request->alamat,
            'gaji_pokok' => $request->gaji_pokok,
            'status_ptkp' => $request->status_ptkp,
            'id_lokasi_kantor' => $request->id_lokasi_kantor,
            'is_strict_location' => $request->is_strict_location ?? false,
        ]);

        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($karyawan->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($karyawan->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($karyawan->foto);
            }
            $path = $request->file('foto')->store('karyawan_fotos', 'public');
            $karyawan->update(['foto' => $path]);
        }

        if ($request->nama_bank && $request->nomor_rekening) {
            $karyawan->rekeningBank()->updateOrCreate(
                ['is_primary' => true],
                [
                    'nama_bank' => $request->nama_bank,
                    'nomor_rekening' => $request->nomor_rekening,
                    'atas_nama_rekening' => $request->atas_nama_rekening ?? $request->nama_lengkap,
                ]
            );
        }

        if ($karyawan->user) {
            $karyawan->user->update(['name' => $request->nama_lengkap]);
        }

        return redirect()->back()->with('success', 'Data Karyawan berhasil diperbarui.');
    }

    public function destroy(Karyawan $karyawan)
    {
        try {
            $karyawan->delete();
            return redirect()->back()->with('success', 'Data Karyawan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data.');
        }
    }
}
