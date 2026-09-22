<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\RiwayatKarir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RiwayatKarirController extends Controller
{
    /**
     * Store a newly created career history.
     */
    public function store(Request $request, Karyawan $karyawan)
    {
        $validated = $request->validate([
            'tipe_peristiwa' => 'required|in:Pengangkatan Awal,Perpanjangan Kontrak,Pengangkatan Tetap,Promosi,Demosi,Mutasi,Penyesuaian Gaji,Lainnya,Resign,PHK,Habis Kontrak',
            'tanggal_efektif' => 'required|date',
            'tanggal_berakhir_kontrak' => 'nullable|date',
            'id_departemen' => 'nullable|exists:tbl_departemen,id',
            'jabatan' => 'required|string|max:100',
            'status_karyawan' => 'required|in:Tetap,Kontrak,Magang,Probation',
            'gaji_pokok' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
            'file_sk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        $validated['id_karyawan'] = $karyawan->id;

        if ($request->hasFile('file_sk')) {
            $validated['file_sk'] = $request->file('file_sk')->store('karyawan_docs', 'public');
        }

        // Simpan Riwayat
        RiwayatKarir::create($validated);

        // Cek jika tipe peristiwanya adalah pengakhiran kerja
        if (in_array($validated['tipe_peristiwa'], ['Resign', 'PHK', 'Habis Kontrak'])) {
            // Cabut hak akses (unlink user)
            if ($karyawan->id_user) {
                $user = \App\Models\User::find($karyawan->id_user);
                if ($user) {
                    $user->delete(); // atau revoke roles
                }
                $karyawan->id_user = null;
                $karyawan->save();
            }
            // Soft delete karyawan
            $karyawan->delete();
            return redirect()->back()->with('success', 'Riwayat ditambahkan. Karyawan telah di-nonaktifkan dan hak akses dicabut.');
        }

        // LOGIKA OTOMATIS: Update data Karyawan jika tanggal_efektif >= hari ini ATAU ini adalah data paling baru
        $latestHistory = $karyawan->riwayatKarir()->orderBy('tanggal_efektif', 'desc')->first();
        
        // Kita perbarui tabel Karyawan dengan data history yang paling "terbaru" (berdasarkan tanggal efektif)
        if ($latestHistory && $latestHistory->tanggal_efektif->lte(now())) {
            $karyawan->update([
                'jabatan' => $latestHistory->jabatan,
                'status_karyawan' => $latestHistory->status_karyawan,
                'gaji_pokok' => $latestHistory->gaji_pokok,
                'id_departemen' => $latestHistory->id_departemen,
            ]);
        }

        return redirect()->back()->with('success', 'Riwayat karir berhasil ditambahkan dan data utama karyawan telah disesuaikan.');
    }

    /**
     * Remove the specified career history.
     */
    public function destroy(RiwayatKarir $riwayatKarir)
    {
        $karyawanId = $riwayatKarir->id_karyawan;
        $isTermination = in_array($riwayatKarir->tipe_peristiwa, ['Resign', 'PHK', 'Habis Kontrak']);
        
        if ($riwayatKarir->file_sk && Storage::disk('public')->exists($riwayatKarir->file_sk)) {
            Storage::disk('public')->delete($riwayatKarir->file_sk);
        }
        
        $riwayatKarir->delete();

        // Rollback ke riwayat sebelumnya jika ada
        $karyawan = Karyawan::withTrashed()->find($karyawanId);
        
        if ($karyawan) {
            // Restore jika riwayat yang dihapus adalah riwayat pemberhentian
            if ($isTermination && $karyawan->trashed()) {
                $karyawan->restore();
            }

            $latestHistory = $karyawan->riwayatKarir()->orderBy('tanggal_efektif', 'desc')->first();
            
            if ($latestHistory && $latestHistory->tanggal_efektif->lte(now())) {
                $karyawan->update([
                    'jabatan' => $latestHistory->jabatan,
                    'status_karyawan' => $latestHistory->status_karyawan,
                    'gaji_pokok' => $latestHistory->gaji_pokok,
                    'id_departemen' => $latestHistory->id_departemen,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Riwayat karir berhasil dihapus.');
    }
}
