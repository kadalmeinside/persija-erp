<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractTemplateController extends Controller
{
    public function index()
    {
        $templates = ContractTemplate::orderBy('id')->get();

        $availableTags = [
            ['tag' => '{COMPANY_NAME}', 'label' => 'Nama Perusahaan (dari Pengaturan)'],
            ['tag' => '{COMPANY_ADDRESS}', 'label' => 'Alamat Perusahaan (dari Pengaturan)'],
            ['tag' => '{NOMOR_KONTRAK}', 'label' => 'Nomor Kontrak Kerja'],
            ['tag' => '{NAMA_KARYAWAN}', 'label' => 'Nama Lengkap Karyawan'],
            ['tag' => '{NIK_KARYAWAN}', 'label' => 'Nomor Induk Karyawan / NIP'],
            ['tag' => '{TEMPAT_LAHIR}', 'label' => 'Tempat Lahir'],
            ['tag' => '{TANGGAL_LAHIR}', 'label' => 'Tanggal Lahir'],
            ['tag' => '{JENIS_KELAMIN}', 'label' => 'Jenis Kelamin (Laki-laki / Perempuan)'],
            ['tag' => '{ALAMAT_KARYAWAN}', 'label' => 'Alamat Domisili Karyawan'],
            ['tag' => '{JABATAN}', 'label' => 'Jabatan / Posisi Pekerjaan'],
            ['tag' => '{DEPARTEMEN}', 'label' => 'Departemen Karyawan'],
            ['tag' => '{LOKASI_KANTOR}', 'label' => 'Lokasi Penempatan Kantor / Venue'],
            ['tag' => '{TANGGAL_BERGABUNG}', 'label' => 'Tanggal Mulai Kontrak / Bergabung'],
            ['tag' => '{TANGGAL_BERAKHIR}', 'label' => 'Tanggal Berakhir Kontrak'],
            ['tag' => '{DURASI_PROBATION}', 'label' => 'Durasi Masa Percobaan / Evaluasi (Bulan)'],
            ['tag' => '{TANGGAL_SELESAI_PROBATION}', 'label' => 'Tanggal Berakhir Masa Percobaan'],
            ['tag' => '{JAM_KERJA_MINGGUAN}', 'label' => 'Jam Kerja per Minggu (default: 40)'],
            ['tag' => '{NOTICE_PERIOD}', 'label' => 'Periode Notice Period Resign (Hari)'],
            ['tag' => '{GAJI_POKOK}', 'label' => 'Gaji Pokok Karyawan (Rp)'],
            ['tag' => '{TUNJANGAN_RUTIN}', 'label' => 'Daftar Rincian Tunjangan Rutin'],
            ['tag' => '{REKENING_BANK}', 'label' => 'Info Bank & Nomor Rekening'],
            ['tag' => '{STATUS_PTKP}', 'label' => 'Status PTKP Pajak Penghasilan'],
            ['tag' => '{NO_BPJS_KETENAGAKERJAAN}', 'label' => 'Nomor Kartu BPJS Ketenagakerjaan'],
            ['tag' => '{NO_BPJS_KESEHATAN}', 'label' => 'Nomor Kartu BPJS Kesehatan'],
            ['tag' => '{WORKING_SCOPE}', 'label' => 'Daftar Uraian Tugas / Working Scope (HTML List)'],
            ['tag' => '{TANGGAL_HARI_INI}', 'label' => 'Tanggal Hari Ini (Format Indonesia)'],
        ];

        return Inertia::render('Admin/ContractTemplate/Index', [
            'templates'     => $templates,
            'availableTags' => $availableTags,
        ]);
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $validated = $request->validate([
            'nama_template' => 'required|string|max:255',
            'judul_surat'   => 'required|string|max:255',
            'konten'        => 'required|string',
            'is_active'     => 'boolean',
        ]);

        $contractTemplate->update($validated);

        return redirect()->back()->with('success', "Template kontrak {$contractTemplate->nama_template} berhasil diperbarui.");
    }

    public function reset(ContractTemplate $contractTemplate)
    {
        // Re-run the seeder to restore official default clauses
        $seeder = new ContractTemplateSeeder();
        $seeder->run();

        return redirect()->back()->with('success', "Template {$contractTemplate->nama_template} berhasil di-reset ke standar hukum ketenagakerjaan resmi.");
    }
}
