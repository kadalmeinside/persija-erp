<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\LokasiKantor;
use App\Models\ApprovalRule;
use App\Models\ApprovalProcess;
use App\Models\PengajuanHeader;
use App\Models\PengajuanDetail;
use App\Models\Cuti;
use App\Models\Pinjaman;
use App\Models\InvoiceHeader;
use App\Models\InvoiceDetail;
use App\Models\Pelanggan;
use App\Models\AkunPendapatan;
use App\Models\ApprovalDocument;
use Illuminate\Support\Facades\DB;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

class E2ETestingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Mempersiapkan E2E Testing Seeder...');

        DB::beginTransaction();

        try {
            // Pastikan roles & permissions sudah ada
            $this->call(RoleAndPermissionSeeder::class);
            $this->call(DepartemenSeeder::class);
            $this->call(ApprovalRuleSeeder::class);
            
            // Siapkan Departemen & Lokasi Dasar
            $deptFinance = Departemen::firstOrCreate(['nama_departemen' => 'Finance']);
            $deptHr = Departemen::firstOrCreate(['nama_departemen' => 'HR']);
            $deptIT = Departemen::firstOrCreate(['nama_departemen' => 'IT']);
            $deptExecutive = Departemen::firstOrCreate(['nama_departemen' => 'Executive']);
            $lokasi = LokasiKantor::firstOrCreate(
                ['nama_kantor' => 'Head Office Testing'],
                ['latitude' => -6.200000, 'longitude' => 106.816666, 'radius_meter' => 100, 'is_active' => true]
            );

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            // Hapus data testing sebelumnya (berdasarkan email testing)
            User::whereIn('email', [
                'test.direktur@persija.id', 
                'test.finance@persija.id', 
                'test.hr@persija.id',
                'test.staf1@persija.id',
                'test.staf2@persija.id'
            ])->forceDelete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->command->info('1. Membuat Karyawan (Direktur, Finance, HR, Staf)...');

            $direktur = $this->createUserAndKaryawan('Direktur Testing', 'test.direktur@persija.id', 'Direktur', $deptExecutive->id, $lokasi->id);
            $finance = $this->createUserAndKaryawan('Finance Testing', 'test.finance@persija.id', 'Finance', $deptFinance->id, $lokasi->id);
            $hr = $this->createUserAndKaryawan('HR Testing', 'test.hr@persija.id', 'HR Staff', $deptHr->id, $lokasi->id);
            $staf1 = $this->createUserAndKaryawan('Staf 1 Testing', 'test.staf1@persija.id', 'Staf', $deptIT->id, $lokasi->id);
            $staf2 = $this->createUserAndKaryawan('Staf 2 Testing', 'test.staf2@persija.id', 'Staf', $deptIT->id, $lokasi->id);

            ApprovalRule::where('tipe', 'Pengajuan')->delete();
            ApprovalRule::create(['id_departemen' => $deptIT->id, 'tipe' => 'Pengajuan', 'level_order' => 1, 'id_karyawan_approver' => $direktur->id, 'label_aksi' => 'Disetujui Lvl 1']);
            ApprovalRule::create(['id_departemen' => $deptIT->id, 'tipe' => 'Pengajuan', 'level_order' => 2, 'id_karyawan_approver' => $finance->id, 'label_aksi' => 'Disetujui Lvl 2']);

            ApprovalRule::where('tipe', 'Cuti')->delete();
            ApprovalRule::create(['id_departemen' => $deptIT->id, 'tipe' => 'Cuti', 'level_order' => 1, 'id_karyawan_approver' => $hr->id, 'label_aksi' => 'Disetujui Lvl 1']);
            ApprovalRule::create(['id_departemen' => $deptIT->id, 'tipe' => 'Cuti', 'level_order' => 2, 'id_karyawan_approver' => $direktur->id, 'label_aksi' => 'Disetujui Lvl 2']);

            ApprovalRule::where('tipe', 'Pinjaman')->delete();
            ApprovalRule::create(['id_departemen' => $deptIT->id, 'tipe' => 'Pinjaman', 'level_order' => 1, 'id_karyawan_approver' => $direktur->id, 'label_aksi' => 'Disetujui Lvl 1']);

            ApprovalRule::where('tipe', 'Invoice')->delete();
            ApprovalRule::create(['id_departemen' => $deptFinance->id, 'tipe' => 'Invoice', 'level_order' => 1, 'id_karyawan_approver' => $finance->id, 'label_aksi' => 'Disetujui Lvl 1']);
            ApprovalRule::create(['id_departemen' => $deptFinance->id, 'tipe' => 'Invoice', 'level_order' => 2, 'id_karyawan_approver' => $direktur->id, 'label_aksi' => 'Disetujui Lvl 2']);

            $service = app(ApprovalService::class);

            $this->command->info('2. Membuat Skenario Pengajuan Dana...');
            $program = \App\Models\ProgramKerja::firstOrCreate(['nama_program' => 'Program Testing E2E', 'id_departemen' => $deptIT->id]);
            $akun = \App\Models\AkunGl::firstOrCreate(['kode_akun' => 'TEST-001', 'nama_akun' => 'Akun Testing', 'tipe_akun' => 'Biaya']);
            
            $pos = \App\Models\PosAnggaran::firstOrCreate([
                'id_program_kerja' => $program->id,
                'id_akun_gl' => $akun->id,
            ]);
            
            $periode = \App\Models\PeriodeAnggaran::firstOrCreate(
                [
                    'nama_periode' => 'Periode ' . Carbon::now()->format('F Y'),
                    'tanggal_mulai' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                    'tanggal_selesai' => Carbon::now()->endOfMonth()->format('Y-m-d')
                ],
                ['is_active' => true]
            );
            
            \App\Models\BudgetMaster::firstOrCreate([
                'id_periode_anggaran' => $periode->id,
                'id_pos_anggaran' => $pos->id,
            ], [
                'anggaran_total_tahun' => 50000000,
                'anggaran_terikat_ytd' => 0,
                'anggaran_realisasi_ytd' => 0
            ]);
            
            $pengajuan = PengajuanHeader::create([
                'id_pengaju' => $staf1->id,
                'id_departemen' => $deptIT->id,
                'nomor_pengajuan' => 'REQ-TEST-' . time(),
                'judul_pengajuan' => 'Pembelian Laptop Testing E2E',
                'tgl_pengajuan' => Carbon::now()->format('Y-m-d'),
                'tipe_pengajuan' => 'Langsung',
                'metode_pembayaran' => 'Transfer',
                'total_nominal_diajukan' => 5000000,
                'catatan_header' => 'Kebutuhan mendesak untuk tim IT',
                
                // Simulasi input form pengaju (bank dan lampiran)
                'id_karyawan_penerima' => $staf1->id,
                'bank_tujuan' => 'BCA',
                'no_rek_tujuan' => '1234567890',
                'atas_nama_tujuan' => 'Staf 1 Testing',
                'attachment_path' => 'pengajuan/dummy-receipt.pdf',
                
                'status_global' => 'Draft',
            ]);
            PengajuanDetail::create([
                'id_pengajuan' => $pengajuan->id,
                'deskripsi_item' => 'Laptop',
                'nominal_item' => 5000000,
                'id_program' => $program->id,
                'id_akun' => $akun->id
            ]);
            $pengajuan->update(['status_global' => 'Pending Approval']);
            $service->initApproval($pengajuan);

            $this->command->info('3. Membuat Skenario Cuti...');
            $jenisCuti = \App\Models\JenisCuti::firstOrCreate(
                ['nama_cuti' => 'Cuti Tahunan E2E'],
                ['kuota_default' => 12]
            );
            $cuti = \App\Models\PengajuanCuti::create([
                'id_karyawan' => $staf2->id,
                'id_jenis_cuti' => $jenisCuti->id,
                'tgl_mulai' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'tgl_selesai' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'jumlah_hari' => 4,
                'alasan' => 'Cuti Tahunan E2E Testing',
                'status' => 'Pending'
            ]);
            $service->initApproval($cuti);

            $this->command->info('4. Membuat Skenario Pinjaman...');
            $pinjaman = \App\Models\Pinjaman::create([
                'id_karyawan' => $staf1->id,
                'tanggal_pengajuan' => Carbon::now()->format('Y-m-d'),
                'jumlah_pinjaman' => 2000000,
                'tenor_bulan' => 6,
                'bunga_persen' => 0,
                'jumlah_angsuran_per_bulan' => 333333,
                'keterangan' => 'Kebutuhan Mendesak E2E',
                'status' => 'Pending Approval'
            ]);
            $service->initApproval($pinjaman);

            $this->command->info('5. Membuat Skenario Invoice (Menunggu Approval Direktur)...');
            $pelanggan = \App\Models\Pelanggan::firstOrCreate(['nama_pelanggan' => 'PT Testing E2E'], ['kode_pelanggan' => 'PEL-TEST', 'email' => 'test@e2e.com']);
            // Note: In original code there was AkunPendapatan, I'll ignore it because it's not used.
            $invoice = InvoiceHeader::create([
                'nomor_invoice' => 'INV-TEST-' . time(),
                'id_departemen' => $deptFinance->id,
                'id_pelanggan' => $pelanggan->id,
                'tgl_invoice' => Carbon::now()->format('Y-m-d'),
                'tgl_jatuh_tempo' => Carbon::now()->addDays(30)->format('Y-m-d'),
                'subtotal' => 10000000,
                'ppn_rate' => 11,
                'ppn_amount' => 1100000,
                'pph_rate' => 0,
                'pph_amount' => 0,
                'total_tagihan' => 11100000,
                'sisa_tagihan' => 11100000,
                'status' => 'Draft',
                'created_by' => $finance->user_id
            ]);
            \App\Models\InvoiceDetail::create([
                'id_invoice' => $invoice->id,
                'deskripsi_item' => 'Layanan Konsultasi Testing',
                'kuantitas' => 1,
                'harga_satuan' => 10000000,
                'total_harga' => 10000000,
                'id_akun_pendapatan' => $akun->id
            ]);
            $service->initApproval($invoice);
            
            // Auto approve level 1 untuk Invoice agar nyangkut di Direktur
            $service->approve($invoice, $finance->id, 'Approved by Finance');

            DB::commit();

            $this->command->info('----------------------------------------------------');
            $this->command->info('🚀 E2E Testing Seeder Berhasil Dijalankan!');
            $this->command->info('Gunakan Akun Berikut untuk Pengujian UI:');
            $this->command->info('----------------------------------------------------');
            $this->command->info('1. Direktur : test.direktur@persija.id / password');
            $this->command->info('2. Finance  : test.finance@persija.id / password');
            $this->command->info('3. HR       : test.hr@persija.id / password');
            $this->command->info('4. Staf 1   : test.staf1@persija.id / password');
            $this->command->info('5. Staf 2   : test.staf2@persija.id / password');
            $this->command->info('----------------------------------------------------');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error occurred: ' . $e->getMessage());
        }
    }

    private function createUserAndKaryawan($name, $email, $roleName, $deptId, $lokasiId)
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->assignRole($role);
        }

        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'nomor_induk_karyawan' => 'TEST-' . rand(1000, 9999),
            'nama_lengkap' => $name,
            'id_departemen' => $deptId,
            'id_lokasi_kantor' => $lokasiId,
            'status_karyawan' => 'Tetap',
            'jabatan' => $roleName,
            'tgl_bergabung' => Carbon::now()->subYears(2)->format('Y-m-d')
        ]);

        return $karyawan;
    }
}
