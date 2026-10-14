<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\User;
use App\Models\SaldoCuti;
use App\Models\Setting;
use App\Models\KaryawanDokumen;
use App\Models\KaryawanKomponenGaji;
use App\Models\GajiKomponen;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $karyawan->load([
            'departemen.kepala',
            'user',
            'rekeningBank',
            'riwayatKarir.departemen',
            'latestRiwayatKarir',
            'lokasiKantor',
            'dokumen.uploader',
            'komponenGaji.komponenGaji',
            'tasksAssigned' => fn($q) => $q->latest()->take(5),
        ]);
        $karyawan->append('primary_bank');
        
        // Load Leave Balances
        $currentYear = date('Y');
        $leaveBalances = SaldoCuti::with('jenisCuti')
                            ->where('id_karyawan', $karyawan->id)
                            ->where('tahun_periode', $currentYear)
                            ->get();

        $departemens = \App\Models\Departemen::all(); // Needed for Edit Form
        $lokasiKantors = \App\Models\LokasiKantor::where('is_active', true)->get();
        $masterKomponenGaji = GajiKomponen::orderBy('nama_komponen')->get();

        // Check last attendance
        $lastAttendance = \App\Models\Absensi::where('id_karyawan', $karyawan->id)
            ->latest('tanggal')
            ->first();

        // Pagination metadata (all employee IDs)
        $allIds = Karyawan::whereNull('deleted_at')->orderBy('id', 'asc')->pluck('id')->toArray();
        $currentIndex = array_search($karyawan->id, $allIds);
        $prevId = ($currentIndex !== false && $currentIndex > 0) ? $allIds[$currentIndex - 1] : null;
        $nextId = ($currentIndex !== false && $currentIndex < count($allIds) - 1) ? $allIds[$currentIndex + 1] : null;

        return Inertia::render('Admin/Karyawan/Show', [
            'karyawan' => $karyawan,
            'leaveBalances' => $leaveBalances,
            'departemens' => $departemens,
            'lokasi_kantors' => $lokasiKantors,
            'master_komponen_gaji' => $masterKomponenGaji,
            'lastClockIn' => $lastAttendance ? ($lastAttendance->tanggal . ' ' . ($lastAttendance->jam_masuk ?? '')) : null,
            'navigation' => [
                'current' => $currentIndex !== false ? $currentIndex + 1 : 1,
                'total' => count($allIds),
                'prev_id' => $prevId,
                'next_id' => $nextId,
            ]
        ]);
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'sometimes|required|string|max:255',
            'nomor_induk_karyawan' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('tbl_karyawan')->ignore($karyawan->id)->whereNull('deleted_at')],
            'id_departemen' => 'sometimes|required|exists:tbl_departemen,id',
            'jenis_kelamin' => 'sometimes|required|in:L,P',
            'jabatan' => 'sometimes|required|string|max:100',
            'status_karyawan' => 'sometimes|required|in:Tetap,Kontrak,Magang,Probation',
            'foto' => 'nullable|image|max:2048',
            'id_lokasi_kantor' => 'nullable|exists:tbl_lokasi_kantor,id',
            'is_strict_location' => 'boolean',
            'tgl_bergabung' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:255',
            'tgl_lahir' => 'nullable|date',
            'alamat' => 'nullable|string',
            'gaji_pokok' => 'nullable|numeric',
            'status_ptkp' => 'nullable|string|max:50',
            'nama_bank' => 'nullable|string|max:100',
            'nomor_rekening' => 'nullable|string|max:100',
            'atas_nama_rekening' => 'nullable|string|max:255',
        ]);

        $dataToUpdate = collect($validated)->except(['foto', 'nama_bank', 'nomor_rekening', 'atas_nama_rekening'])->toArray();
        
        if ($request->has('is_strict_location')) {
            $dataToUpdate['is_strict_location'] = filter_var($request->is_strict_location, FILTER_VALIDATE_BOOLEAN);
        }

        if (!empty($dataToUpdate)) {
            $karyawan->update($dataToUpdate);
        }

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

        if ($karyawan->user && $request->has('nama_lengkap')) {
            $karyawan->user->update(['name' => $request->nama_lengkap]);
        }

        return redirect()->back()->with('success', 'Data Karyawan berhasil diperbarui.');
    }

    public function generateUser(Karyawan $karyawan)
    {
        if ($karyawan->user_id) {
            return redirect()->back()->with('error', 'Karyawan ini sudah memiliki akun login.');
        }

        $namaParts = explode(' ', trim($karyawan->nama_lengkap));
        $namaDepan = strtolower(preg_replace('/[^a-zA-Z]/', '', $namaParts[0]));
        if (empty($namaDepan)) {
            $namaDepan = 'user';
        }

        $email = "{$namaDepan}.{$karyawan->id}@persijadevelopment.id";
        
        DB::transaction(function () use ($karyawan, $email) {
            $user = User::create([
                'name' => $karyawan->nama_lengkap,
                'email' => $email,
                'password' => Hash::make('persija123'),
                'pin' => Hash::make('123456'), // PIN Default
            ]);
            
            $user->assignRole('Karyawan');
            $karyawan->update(['user_id' => $user->id]);
        });
        
        return redirect()->back()->with('success', "Akun login berhasil dibuat (Email: {$email}, Password: persija123).");
    }

    public function updateContractDetails(Request $request, Karyawan $karyawan)
    {
        $validated = $request->validate([
            'nomor_kontrak' => 'nullable|string|max:100',
            'contract_type' => 'required|string|in:PKWT,PKWTT,Freelance,Internship,Professional',
            'durasi_probation_bulan' => 'nullable|integer|min:0|max:12',
            'tanggal_selesai_probation' => 'nullable|date',
            'notice_period_hari' => 'nullable|integer|min:0|max:180',
            'jam_kerja_mingguan' => 'nullable|integer|min:1|max:80',
            'working_scope' => 'nullable|array',
            'working_scope.*' => 'required|string|max:500',
            'id_lokasi_kantor' => 'nullable|exists:tbl_lokasi_kantor,id',
            'is_strict_location' => 'boolean',
        ]);

        DB::transaction(function () use ($karyawan, $validated) {
            $karyawan->update([
                'nomor_kontrak' => $validated['nomor_kontrak'] ?? null,
                'contract_type' => $validated['contract_type'],
                'durasi_probation_bulan' => $validated['durasi_probation_bulan'] ?? 3,
                'tanggal_selesai_probation' => $validated['tanggal_selesai_probation'] ?? null,
                'notice_period_hari' => $validated['notice_period_hari'] ?? 30,
                'jam_kerja_mingguan' => $validated['jam_kerja_mingguan'] ?? 40,
                'working_scope' => !empty($validated['working_scope']) ? array_values(array_filter($validated['working_scope'])) : null,
                'id_lokasi_kantor' => $validated['id_lokasi_kantor'] ?? $karyawan->id_lokasi_kantor,
                'is_strict_location' => $validated['is_strict_location'] ?? $karyawan->is_strict_location,
            ]);
        });

        return redirect()->back()->with('success', 'Rincian klausul kontrak & lingkup kerja berhasil diperbarui.');
    }

    public function uploadDokumen(Request $request, Karyawan $karyawan)
    {
        $validated = $request->validate([
            'kategori_dokumen' => 'required|string|in:kontrak,sk,ktp,npwp,bpjs,ijazah_sertifikat,lainnya',
            'judul_dokumen' => 'required|string|max:255',
            'nomor_dokumen' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'tanggal_terbit' => 'nullable|date',
            'tanggal_kadaluarsa' => 'nullable|date',
            'catatan' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $path = $file->store("karyawan_dokumen/{$karyawan->id}", 'public');

        KaryawanDokumen::create([
            'id_karyawan' => $karyawan->id,
            'kategori_dokumen' => $validated['kategori_dokumen'],
            'judul_dokumen' => $validated['judul_dokumen'],
            'nomor_dokumen' => $validated['nomor_dokumen'] ?? null,
            'file_path' => $path,
            'file_size' => $fileSize,
            'file_extension' => $extension,
            'tanggal_terbit' => $validated['tanggal_terbit'] ?? null,
            'tanggal_kadaluarsa' => $validated['tanggal_kadaluarsa'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Dokumen berhasil diunggah dan disimpan ke berkas karyawan.');
    }

    public function destroyDokumen(KaryawanDokumen $dokumen)
    {
        if ($dokumen->file_path && Storage::disk('public')->exists($dokumen->file_path)) {
            Storage::disk('public')->delete($dokumen->file_path);
        }
        $dokumen->delete();

        return redirect()->back()->with('success', 'Dokumen berhasil dihapus.');
    }

    public function syncKomponenGaji(Request $request, Karyawan $karyawan)
    {
        $validated = $request->validate([
            'gaji_pokok' => 'required|numeric|min:0',
            'status_ptkp' => 'nullable|string|max:20',
            'no_bpjs_ketenagakerjaan' => 'nullable|string|max:50',
            'no_bpjs_kesehatan' => 'nullable|string|max:50',
            'nama_bank' => 'nullable|string|max:100',
            'nomor_rekening' => 'nullable|string|max:100',
            'atas_nama_rekening' => 'nullable|string|max:255',
            'komponen' => 'nullable|array',
            'komponen.*.id_komponen_gaji' => 'required|exists:tbl_gaji_komponen,id',
            'komponen.*.nominal' => 'required|numeric|min:0',
            'komponen.*.keterangan' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($karyawan, $validated, $request) {
            $karyawan->update([
                'gaji_pokok' => $validated['gaji_pokok'],
                'status_ptkp' => $validated['status_ptkp'] ?? $karyawan->status_ptkp,
                'no_bpjs_ketenagakerjaan' => $validated['no_bpjs_ketenagakerjaan'] ?? null,
                'no_bpjs_kesehatan' => $validated['no_bpjs_kesehatan'] ?? null,
            ]);

            if ($request->nama_bank && $request->nomor_rekening) {
                $karyawan->rekeningBank()->updateOrCreate(
                    ['is_primary' => true],
                    [
                        'nama_bank' => $request->nama_bank,
                        'nomor_rekening' => $request->nomor_rekening,
                        'atas_nama_rekening' => $request->atas_nama_rekening ?? $karyawan->nama_lengkap,
                    ]
                );
            }

            // Sync komponen tunjangan / potongan rutin
            KaryawanKomponenGaji::where('id_karyawan', $karyawan->id)->delete();

            if (!empty($validated['komponen'])) {
                foreach ($validated['komponen'] as $item) {
                    if (($item['nominal'] ?? 0) > 0) {
                        KaryawanKomponenGaji::create([
                            'id_karyawan' => $karyawan->id,
                            'id_komponen_gaji' => $item['id_komponen_gaji'],
                            'nominal' => $item['nominal'],
                            'keterangan' => $item['keterangan'] ?? null,
                            'is_active' => true,
                        ]);
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Data kompensasi, benefit & tunjangan berhasil diperbarui.');
    }

    public function downloadContractPdf(Karyawan $karyawan)
    {
        $karyawan->load([
            'departemen.kepala',
            'lokasiKantor',
            'rekeningBank',
            'latestRiwayatKarir',
            'komponenGaji.komponenGaji'
        ]);

        // Fetch Company Settings from centralized Settings table (Konsisten dengan modul Pengajuan & Payroll)
        $settings = Setting::all()->pluck('value', 'key');
        
        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            $path = storage_path('app/public/' . $settings['company_logo']);
            if (file_exists($path)) {
                $logoPath = $path;
            }
        }

        $company = [
            'name'      => $settings['company_name'] ?? 'PT PERSIJA JAYA JAKARTA',
            'address'   => $settings['company_address'] ?? 'Rasuna Office Park, Jl. HR Rasuna Said, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        $pdf = Pdf::loadView('pdf.kontrak_kerja', compact('karyawan', 'company'));
        $pdf->setPaper('a4', 'portrait');

        $cleanNik = preg_replace('/[^A-Za-z0-9_\-]/', '_', $karyawan->nomor_induk_karyawan ?? 'KARYAWAN');
        return $pdf->download("Perjanjian_Kerja_{$cleanNik}.pdf");
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
