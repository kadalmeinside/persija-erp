<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\PengajuanHeader;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRule;
use App\Enums\PengajuanStatus;
use App\Enums\PaymentMethod;

class PengajuanRevisionFixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bypass auth + Spatie permission, tapi pertahankan route model binding & validation
        $this->withoutMiddleware([
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Auth\Middleware\Authenticate::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    }

    public function test_updating_revision_pengajuan_resets_approval_process()
    {
        // 1. Setup User & Departemen
        $dept = Departemen::create(['nama_departemen' => 'IT', 'kode_departemen' => 'IT']);
        $user = User::factory()->create();

        $karyawan = Karyawan::create([
            'user_id'              => $user->id,
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Pengaju',
            'nomor_induk_karyawan' => '1001',
            'jenis_kelamin'        => 'L',
            'status_karyawan'      => 'Tetap',
            'jabatan'              => 'Staff IT',
        ]);

        // Buat 2 dummy approver karyawan (real, bukan ID 999/888)
        $approver1User = User::factory()->create();
        $approver1 = Karyawan::create([
            'user_id'              => $approver1User->id,
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Manajer 1',
            'nomor_induk_karyawan' => '1002',
            'jenis_kelamin'        => 'L',
            'status_karyawan'      => 'Tetap',
            'jabatan'              => 'Manajer',
        ]);

        $approver2User = User::factory()->create();
        $approver2 = Karyawan::create([
            'user_id'              => $approver2User->id,
            'id_departemen'        => $dept->id,
            'nama_lengkap'         => 'Direktur',
            'nomor_induk_karyawan' => '1003',
            'jenis_kelamin'        => 'L',
            'status_karyawan'      => 'Tetap',
            'jabatan'              => 'Direktur',
        ]);

        // 2. Buat Pengajuan dengan Status REVISION
        $pengajuan = PengajuanHeader::create([
            'nomor_pengajuan'        => 'TEST/REV/001',
            'judul_pengajuan'        => 'Pembelian Laptop (Revisi Pertama)',
            'id_pengaju'             => $karyawan->id,
            'id_departemen'          => $dept->id,
            'tgl_pengajuan'          => now(),
            'tipe_pengajuan'         => 'Langsung',
            'total_nominal_diajukan' => 15000000,
            'status_global'          => PengajuanStatus::REVISION,
        ]);

        // 3. Simulasikan Approval Process — Level 1 Approved, Level 2 Revision
        ApprovalProcess::create([
            'id_pengajuan'       => $pengajuan->id,
            'level_order'        => 1,
            'status'             => 'Approved',
            'id_karyawan_target' => $approver1->id,
            'label_aksi'         => 'Approval Manajer',
        ]);
        ApprovalProcess::create([
            'id_pengajuan'       => $pengajuan->id,
            'level_order'        => 2,
            'status'             => 'Revision',
            'id_karyawan_target' => $approver2->id,
            'label_aksi'         => 'Approval Direksi',
        ]);

        $this->assertCount(2, $pengajuan->approvalProcess);

        // 4. Buat ApprovalRule agar initApproval punya patokan
        ApprovalRule::create([
            'id_departemen'        => $dept->id,
            'tipe'                 => 'Pengajuan',
            'level_order'          => 1,
            'id_karyawan_approver' => $approver1->id,
            'label_aksi'           => 'Approval Baru',
        ]);

        // Buat FK depedency untuk validasi UpdatePengajuanRequest
        $program = \App\Models\ProgramKerja::create(['nama_program' => 'Program Test', 'id_departemen' => $dept->id]);
        $akun    = \App\Models\AkunGl::create(['kode_akun' => '6001', 'nama_akun' => 'Biaya Test', 'tipe_akun' => 'Biaya']);
        $vendor  = \App\Models\Vendor::create([
            'kode_vendor'  => 'V001',
            'nama_vendor'  => 'Vendor Test',
            'jenis_vendor' => 'Vendor',
        ]);

        // Mock BudgetCheckService agar lolos
        $this->mock(\App\Services\BudgetCheckService::class, function ($mock) {
            $mock->shouldReceive('check')->andReturn(['success' => true, 'warning' => null, 'budget_id' => null]);
            $mock->shouldReceive('commitBudget')->andReturn(true);
            $mock->shouldReceive('unCommitBudget')->andReturn(true);
            $mock->shouldReceive('getBudgetId')->andReturn(null);  // reversalBudget
        });

        // 5. Submit ulang via Controller Update
        $payload = [
            'id_pengaju'             => $karyawan->id,
            'id_departemen'          => $dept->id,
            'judul_pengajuan'        => 'Pembelian Laptop (Revisi Disubmit)',
            'tgl_pengajuan'          => now()->format('Y-m-d'),
            'tipe_pengajuan'         => \App\Enums\PengajuanType::LANGSUNG->value,
            'total_nominal_diajukan' => 14000000,
            'metode_pembayaran'      => PaymentMethod::TRANSFER->value,
            'catatan_header'         => 'Sudah direvisi sesuai arahan',
            'sub_tipe_penerima'      => 'Vendor',
            'id_vendor_penerima'     => $vendor->id,
            'items' => [
                [
                    'deskripsi_item' => 'Laptop',
                    'nominal_item'   => 14000000,
                    'id_program'     => $program->id,
                    'id_akun'        => $akun->id,
                ],
            ],
        ];

        $this->withoutExceptionHandling();
        $response = $this->actingAs($user)->put(route('admin.pengajuan.update', $pengajuan->id), $payload);
        $response->assertRedirect();
        $response->assertSessionMissing('error'); // Pastikan tidak ada exception
        $response->assertSessionHasNoErrors();    // Pastikan tidak ada validasi error
        $response->assertRedirect(route('admin.pengajuan.index')); // Pastikan redirect ke index (bukan back)

        // 6. Validasi
        $pengajuan->refresh();

        // DEBUG: cek berapa rule dan steps di DB
        $ruleCount = \App\Models\ApprovalRule::count();
        $allSteps  = \App\Models\ApprovalProcess::where('id_pengajuan', $pengajuan->id)->get(['level_order', 'status', 'id_karyawan_target']);

        // 6a. Semua step lama musnah; hanya step dari initApproval yang tersisa (1 step)
        $stepCount = $allSteps->count();
        $this->assertEquals(1, $stepCount, "Jumlah step: {$stepCount}, Rules: {$ruleCount}. Steps: " . $allSteps->toJson());

        // 6b. Status global berubah ke Menunggu Persetujuan
        $this->assertEquals(PengajuanStatus::PENDING_APPROVAL, $pengajuan->status_global);

        // 6c. Step baru ada di status Pending
        $this->assertEquals('Pending', $pengajuan->approvalProcess->first()->status);
    }
}
