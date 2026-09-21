<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\ApprovalRule;
use App\Models\AkunGl;
use App\Models\ProgramKerja;
use App\Models\PosAnggaran;
use App\Models\KasBank;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role; // Pastikan package Spatie sudah terinstall

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // 0. SETUP ROLES & PERMISSION
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat Role jika belum ada
        $roleDirektur = Role::firstOrCreate(['name' => 'Direktur']);
        $roleManajer  = Role::firstOrCreate(['name' => 'Manajer Departemen']);
        $roleFinance  = Role::firstOrCreate(['name' => 'Finance']);
        $roleStaf     = Role::firstOrCreate(['name' => 'Staf']);

        // 1. SETUP DEPARTEMEN
        $deptIT = Departemen::firstOrCreate(['nama_departemen' => 'Information Technology']);
        $deptHR = Departemen::firstOrCreate(['nama_departemen' => 'Human Resources']);
        $deptFin = Departemen::firstOrCreate(['nama_departemen' => 'Finance & Accounting']);

        // ...


        // 2. SETUP MASTER BUDGET (Updated for New Structure)
        // Program "Operasional Kantor" milik "Finance & Accounting"
        $progOps = ProgramKerja::firstOrCreate(
            ['nama_program' => 'Operasional Kantor'],
            ['id_departemen' => $deptFin->id]
        );

        // Program "Proyek Implementasi ERP" milik "IT"
        $progProyek = ProgramKerja::firstOrCreate(
            ['nama_program' => 'Proyek Implementasi ERP'],
            ['id_departemen' => $deptIT->id]
        );
        
        // Buat Akun GL
        $glPerjalanan = AkunGl::firstOrCreate(['kode_akun' => '6-001', 'nama_akun' => 'Biaya Perjalanan Dinas', 'tipe_akun' => 'Biaya']);
        $glAkomodasi  = AkunGl::firstOrCreate(['kode_akun' => '6-002', 'nama_akun' => 'Biaya Akomodasi', 'tipe_akun' => 'Biaya']);
        $glSewaServer = AkunGl::firstOrCreate(['kode_akun' => '6-003', 'nama_akun' => 'Biaya Sewa Server', 'tipe_akun' => 'Biaya']); // New for IT
        
        // 3. SETUP POS ANGGARAN (Mapping Program <-> Akun)
        // Program Ops Kantor boleh pakai akun Perjalanan & Akomodasi
        PosAnggaran::firstOrCreate(['id_program_kerja' => $progOps->id, 'id_akun_gl' => $glPerjalanan->id]);
        PosAnggaran::firstOrCreate(['id_program_kerja' => $progOps->id, 'id_akun_gl' => $glAkomodasi->id]);

        // Program Proyek ERP (IT) boleh pakai akun Sewa Server & Perjalanan
        PosAnggaran::firstOrCreate(['id_program_kerja' => $progProyek->id, 'id_akun_gl' => $glSewaServer->id]);
        PosAnggaran::firstOrCreate(['id_program_kerja' => $progProyek->id, 'id_akun_gl' => $glPerjalanan->id]);

        // Delegasi: Izinkan Dept IT pakai akun Perjalanan milik Program Ops Kantor (Finance)
        // (Contoh kasus: IT mau dinas tapi budgetnya numpang di program operasional kantor)
        $posOpsPerjalanan = PosAnggaran::where('id_program_kerja', $progOps->id)->where('id_akun_gl', $glPerjalanan->id)->first();
        if ($posOpsPerjalanan) {
            $posOpsPerjalanan->delegasi()->syncWithoutDetaching([$deptIT->id]);
        }

        $glKasKecil = AkunGl::firstOrCreate(['kode_akun' => '1-1100', 'nama_akun' => 'Kas Kecil (Petty Cash)', 'tipe_akun' => 'Aset']);
        $glBankMasuk = AkunGl::firstOrCreate(['kode_akun' => '1-1101', 'nama_akun' => 'Bank Masuk', 'tipe_akun' => 'Aset']);
        $glBankKeluar = AkunGl::firstOrCreate(['kode_akun' => '1-1102', 'nama_akun' => 'Bank Keluar', 'tipe_akun' => 'Aset']);
        $glBankPayroll = AkunGl::firstOrCreate(['kode_akun' => '1-1103', 'nama_akun' => 'Bank Payroll', 'tipe_akun' => 'Aset']);

        // 2. SETUP MASTER KAS/BANK PERUSAHAAN (Tabel Baru)
        // Inilah yang nanti muncul di dropdown saat Finance mau bayar
        KasBank::firstOrCreate(['nomor_rekening' => 'CASH'], [
            'nama_bank' => 'Petty Cash',
            'nomor_rekening' => 'CASH',
            'atas_nama' => 'Kasir Kantor',
            'id_akun_gl' => $glKasKecil->id
        ]);

        KasBank::firstOrCreate(['nomor_rekening' => '000-111-222'], [
            'nama_bank' => 'Bank Masuk',
            'nomor_rekening' => '000-111-222',
            'atas_nama' => 'PT PERSIJA JAYA',
            'id_akun_gl' => $glBankMasuk->id
        ]);

        KasBank::firstOrCreate(['nomor_rekening' => '333-444-555'], [
            'nama_bank' => 'Bank Keluar',
            'nomor_rekening' => '333-444-555',
            'atas_nama' => 'PT PERSIJA JAYA',
            'id_akun_gl' => $glBankKeluar->id
        ]);

        KasBank::firstOrCreate(['nomor_rekening' => '123-000-999'], [
            'nama_bank' => 'Bank Payroll',
            'nomor_rekening' => '123-000-999',
            'atas_nama' => 'PT PERSIJA JAYA',
            'id_akun_gl' => $glBankPayroll->id
        ]);

        // 3. BUAT KARYAWAN + USER + BANK + ROLE
        
        // --- LEVEL DIREKTUR (Approver Tertinggi) ---
        $direktur = $this->createKaryawan('Bambang Direktur', 'bambang', 'Direktur Utama', $deptFin, 'BCA', '88888888', $roleDirektur, 'L');

        // --- DEPT IT ---
        // Siti = Manajer (Bisa approve level 1)
        // Siti = Manajer (Bisa approve level 1)
        $manajerIT = $this->createKaryawan('Siti Manajer IT', 'siti', 'IT Manager', $deptIT, 'Mandiri', '123123123', $roleManajer, 'P');
        // Budi = Staf (Hanya bisa mengajukan, view terbatas miliknya)
        $staffIT = $this->createKaryawan('Budi Staff IT', 'budi', 'IT Support', $deptIT, 'BCA', '111000111', $roleStaf, 'L'); 

        // --- DEPT HRD ---
        $manajerHR = $this->createKaryawan('Rina Manajer HR', 'rina', 'HR Manager', $deptHR, 'BNI', '456456456', $roleManajer, 'P');
        $staffHR = $this->createKaryawan('Dedi Staff HR', 'dedi', 'HR Staff', $deptHR, 'Mandiri', '999000999', $roleStaf, 'L');

        // --- DEPT FINANCE ---
        $staffFin = $this->createKaryawan('Fanny Finance', 'fanny', 'Finance Staff', $deptFin, 'BCA', '55555555', $roleFinance, 'P');

        // 4. SETUP ATURAN APPROVAL (WORKFLOW)
        DB::table('tbl_approval_rules')->truncate();

        // RULE A: Departemen IT (2 Layer: Manajer IT -> Direktur)
        ApprovalRule::create([
            'id_departemen' => $deptIT->id,
            'level_order' => 1,
            'id_karyawan_approver' => $manajerIT->id, // Siti
            'label_aksi' => 'Diketahui Manajer',
        ]);
        ApprovalRule::create([
            'id_departemen' => $deptIT->id,
            'level_order' => 2,
            'id_karyawan_approver' => $direktur->id, // Pak Bambang
            'label_aksi' => 'Disetujui Direktur',
        ]);

        // RULE B: Departemen HRD (1 Layer: Manajer HR saja)
        ApprovalRule::create([
            'id_departemen' => $deptHR->id,
            'level_order' => 1,
            'id_karyawan_approver' => $manajerHR->id, // Rina
            'label_aksi' => 'Disetujui Manajer',
        ]);
        
        // RULE C: Departemen Finance (Self-Approval Test Case)
        ApprovalRule::create([
            'id_departemen' => $deptFin->id,
            'level_order' => 1,
            'id_karyawan_approver' => $direktur->id, // Pak Bambang
            'label_aksi' => 'Disetujui Direktur',
        ]);

        $this->command->info('Dummy Data + Roles berhasil digenerate!');
        $this->command->info('--- AKUN DEMO ---');
        $this->command->info('1. budi@persija.id (Staf) -> Pengaju Biasa');
        $this->command->info('2. siti@persija.id (Manajer) -> Approver L1 IT');
        $this->command->info('3. bambang@persija.id (Direktur) -> Approver L2 / Final');
        $this->command->info('Password semua: password');
    }

    private function createKaryawan($name, $username, $jabatan, $dept, $bank, $rek, $role, $gender = 'L')
    {
        // 1. Create User Login
        $user = User::firstOrCreate(
            ['email' => "$username@persija.id"],
            [
                'name' => $name,
                'password' => Hash::make('password'),
            ]
        );

        // 2. Assign Role (PENTING)
        if ($role) {
            $user->syncRoles($role); // Menggunakan syncRoles agar tidak duplikat jika seeder dijalankan ulang
        }

        // 3. Create Karyawan Profile
        $karyawan = Karyawan::firstOrCreate(
            ['user_id' => $user->id],
            [
                'id_departemen' => $dept->id,
                'nomor_induk_karyawan' => 'EMP-' . strtoupper($username),
                'nama_lengkap' => $name,
                'jenis_kelamin' => $gender,
                'jabatan' => $jabatan,
                'gaji_pokok' => 5000000,
                'status_ptkp' => 'TK/0'
            ]
        );

        // 4. Create Bank Account
        $karyawan->rekeningBank()->firstOrCreate(
            ['nomor_rekening' => $rek],
            [
                'nama_bank' => $bank,
                'atas_nama_rekening' => $name,
                'is_primary' => true
            ]
        );

        return $karyawan;
    }
}