<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AkunGl;
use App\Models\InternalTransfer;
use App\Models\JurnalDetail;
use App\Models\JurnalHeader;
use App\Models\KasBank;
use App\Models\PeriodeClosing;
use App\Models\User;
use App\Services\KasBankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class Phase2BatchATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::cases() as $role) {
            SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }

    public function test_redundant_pdf_image_tools_and_iclock_stub_are_disabled(): void
    {
        $this->assertFalse(Route::has('admin.pdf-tools.index'));
        $this->assertFalse(Route::has('admin.image-tools.index'));
        $this->assertFalse(Route::has('admin.accounting-periods.index'));

        $this->get('/iclock/cdata')->assertNotFound();
    }

    public function test_check_period_lock_uses_single_source_periode_closing(): void
    {
        $finance = User::factory()->create();
        $finance->assignRole(Role::FINANCE_MANAGER->value);

        $akunA = AkunGl::create(['kode_akun' => '1101', 'nama_akun' => 'Kas', 'tipe_akun' => 'Aset']);
        $akunB = AkunGl::create(['kode_akun' => '1102', 'nama_akun' => 'Bank', 'tipe_akun' => 'Aset']);
        $kas = KasBank::create(['nama_bank' => 'Kas', 'nomor_rekening' => '111', 'atas_nama' => 'PT', 'id_akun_gl' => $akunA->id, 'saldo_awal' => 0]);
        $bank = KasBank::create(['nama_bank' => 'Bank', 'nomor_rekening' => '222', 'atas_nama' => 'PT', 'id_akun_gl' => $akunB->id, 'saldo_awal' => 0]);

        PeriodeClosing::create([
            'bulan' => 11,
            'tahun' => 2026,
            'status' => 'Closed',
            'closed_by' => $finance->id,
            'closed_at' => now(),
        ]);

        $res = $this->actingAs($finance)->post(route('admin.internal-transfers.store'), [
            'tgl_transfer' => '2026-11-15',
            'from_kas_bank_id' => $kas->id,
            'to_kas_bank_id' => $bank->id,
            'nominal' => 100000,
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('error');
    }

    public function test_kas_bank_service_and_internal_transfer_role_and_sod(): void
    {
        $akunA = AkunGl::create(['kode_akun' => '1101', 'nama_akun' => 'Kas', 'tipe_akun' => 'Aset']);
        $akunB = AkunGl::create(['kode_akun' => '1102', 'nama_akun' => 'Bank', 'tipe_akun' => 'Aset']);
        $kas = KasBank::create(['nama_bank' => 'Kas', 'nomor_rekening' => '111', 'atas_nama' => 'PT', 'id_akun_gl' => $akunA->id, 'saldo_awal' => 1000000]);
        $bank = KasBank::create(['nama_bank' => 'Bank', 'nomor_rekening' => '222', 'atas_nama' => 'PT', 'id_akun_gl' => $akunB->id, 'saldo_awal' => 0]);

        $user = User::factory()->create(['pin' => Hash::make('123456')]);
        $user->assignRole(Role::FINANCE_MANAGER->value);

        $jurnal = JurnalHeader::create([
            'nomor_jurnal' => 'JRN-001',
            'tgl_jurnal' => now()->toDateString(),
            'deskripsi_jurnal' => 'Setoran',
            'sumber_modul' => 'MANUAL',
            'sumber_id' => 1,
            'status' => 'Posted',
            'created_by' => $user->id,
        ]);
        JurnalDetail::create([
            'id_jurnal' => $jurnal->id,
            'id_akun' => $akunA->id,
            'debit' => 500000,
            'kredit' => 0,
        ]);

        $this->assertEquals(1500000.0, app(KasBankService::class)->getSaldoGl($kas));

        $transfer = InternalTransfer::create([
            'nomor_transfer' => 'IT-2026-001',
            'tgl_transfer' => now()->toDateString(),
            'from_kas_bank_id' => $kas->id,
            'to_kas_bank_id' => $bank->id,
            'nominal' => 200000,
            'status' => 'Draft',
            'created_by' => $user->id,
        ]);

        // Finance Staff cannot approve
        $staff = User::factory()->create(['pin' => Hash::make('123456')]);
        $staff->assignRole(Role::FINANCE_STAFF->value);
        $this->actingAs($staff)
            ->withSession(['approval_pin_verified_at' => now()->toIso8601String()])
            ->post(route('admin.internal-transfers.approve', $transfer->id))
            ->assertForbidden();

        // Creator (Finance Manager) cannot self-approve (SoD)
        $resSod = $this->actingAs($user)
            ->withSession(['approval_pin_verified_at' => now()->toIso8601String()])
            ->post(route('admin.internal-transfers.approve', $transfer->id));
        $resSod->assertSessionHas('error');
        $this->assertEquals('Draft', $transfer->fresh()->status);
    }
}
