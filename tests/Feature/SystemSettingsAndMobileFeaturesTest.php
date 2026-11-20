<?php

namespace Tests\Feature;

use App\Enums\PayrollStatus;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use App\Enums\Role as RoleEnum;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRule;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\PengajuanPembayaran;
use App\Models\JenisCuti;
use App\Models\SaldoCuti;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\ApprovalService;
use App\Services\FcmService;
use App\Services\SystemConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemSettingsAndMobileFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Departemen $departemen;

    protected function setUp(): void
    {
        parent::setUp();

        $perm = Permission::firstOrCreate(['name' => 'settings.manage']);
        $adminRole = Role::firstOrCreate(['name' => RoleEnum::SUPER_ADMIN->value]);
        $adminRole->givePermissionTo($perm);
        Role::firstOrCreate(['name' => RoleEnum::STAF->value]);
        Role::firstOrCreate(['name' => RoleEnum::MANAJER_DEPARTEMEN->value]);

        $this->departemen = Departemen::create([
            'nama_departemen' => 'Operasional',
            'kode_departemen' => 'OPS',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);
    }

    public function test_settings_encrypts_mail_and_fcm_secrets_and_never_leaks_plain_text(): void
    {
        $serviceAccountPayload = json_encode([
            'type' => 'service_account',
            'project_id' => 'persija-erp-prod',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nTESTKEY\n-----END PRIVATE KEY-----\n",
            'client_email' => 'firebase-adminsdk@persija-erp-prod.iam.gserviceaccount.com',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.update'), [
                'app_name' => 'Persija ERP',
                'mail_enabled' => true,
                'mail_mailer' => 'smtp',
                'mail_host' => 'smtp.mailtrap.io',
                'mail_port' => 587,
                'mail_username' => 'mailer@persija.id',
                'mail_password' => 'SuperSecretSmtpPass!',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'noreply@persija.id',
                'mail_from_name' => 'Persija ERP Notification',
                'fcm_enabled' => true,
                'fcm_project_id' => 'persija-erp-prod',
                'fcm_service_account_json' => $serviceAccountPayload,
            ]);

        $response->assertRedirect(route('admin.settings.index'));

        $storedMailPass = Setting::where('key', 'mail_password')->value('value');
        $storedFcmJson = Setting::where('key', 'fcm_service_account_json')->value('value');

        $this->assertNotEquals('SuperSecretSmtpPass!', $storedMailPass);
        $this->assertEquals('SuperSecretSmtpPass!', Crypt::decryptString($storedMailPass));
        $this->assertNotEquals($serviceAccountPayload, $storedFcmJson);
        $this->assertEquals($serviceAccountPayload, Crypt::decryptString($storedFcmJson));

        $this->actingAs($this->adminUser)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Index')
                ->where('settings.mail_host', 'smtp.mailtrap.io')
                ->where('settings.has_mail_password', true)
                ->where('settings.has_fcm_service_account', true)
                ->missing('settings.mail_password')
                ->missing('settings.fcm_service_account_json')
            );
    }

    public function test_forgot_password_applies_db_mail_config_and_forces_rotation(): void
    {
        Mail::fake();
        $configService = app(SystemConfigService::class);

        Setting::updateOrCreate(['key' => 'mail_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'mail_mailer'], ['value' => 'log']);
        Setting::updateOrCreate(['key' => 'mail_from_address'], ['value' => 'erp@persija.id']);
        Setting::updateOrCreate(['key' => 'mail_from_name'], ['value' => 'Persija ERP']);
        $configService->clearCache();

        $staff = User::factory()->create([
            'email' => 'staf.reset@persija.id',
            'must_change_password' => false,
        ]);

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'staf.reset@persija.id',
        ])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $staff->refresh();
        $this->assertTrue($staff->must_change_password);
    }

    public function test_fcm_service_sends_http_v1_push_when_configured(): void
    {
        $keyResource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($keyResource);
        openssl_pkey_export($keyResource, $privateKeyPem);

        $saJson = json_encode([
            'type' => 'service_account',
            'project_id' => 'persija-mobile-fcm',
            'private_key' => $privateKeyPem,
            'client_email' => 'fcm@persija-mobile-fcm.iam.gserviceaccount.com',
        ]);

        $configService = app(SystemConfigService::class);
        Setting::updateOrCreate(['key' => 'fcm_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'fcm_project_id'], ['value' => 'persija-mobile-fcm']);
        Setting::updateOrCreate(['key' => 'fcm_service_account_json'], ['value' => $configService->encryptValue($saJson)]);
        $configService->clearCache();

        $targetUser = User::factory()->create();
        UserDeviceToken::create([
            'user_id' => $targetUser->id,
            'device_token' => 'device-token-abc-123',
            'device_type' => 'android',
            'device_name' => 'Pixel',
            'is_active' => true,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-oauth-token'], 200),
            'https://fcm.googleapis.com/v1/projects/persija-mobile-fcm/messages:send' => Http::response(['name' => 'projects/persija-mobile-fcm/messages/1'], 200),
        ]);

        $result = app(FcmService::class)->sendToUser($targetUser, 'Judul Test', 'Isi Notifikasi', [
            'type' => 'approval',
            'link' => '/approvals',
        ]);

        $this->assertFalse($result['skipped']);
        $this->assertEquals(1, $result['sent']);
        $this->assertEquals(0, $result['failed']);

        Http::assertSentCount(2);
    }

    public function test_mobile_user_can_download_own_payslip_pdf_and_cannot_download_others(): void
    {
        $userA = User::factory()->create();
        $userA->assignRole(RoleEnum::STAF->value);
        $karyawanA = Karyawan::create([
            'user_id' => $userA->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Andi Karyawan',
            'nomor_induk_karyawan' => 'EMP-01',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staff',
        ]);

        $userB = User::factory()->create();
        $userB->assignRole(RoleEnum::STAF->value);
        Karyawan::create([
            'user_id' => $userB->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Budi Karyawan',
            'nomor_induk_karyawan' => 'EMP-02',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staff',
        ]);

        $payroll = Payroll::create([
            'bulan_periode' => '2026-11',
            'tgl_payroll' => '2026-11-25',
            'status' => PayrollStatus::APPROVED,
            'total_gaji_kotor' => 9000000,
            'total_potongan' => 200000,
            'total_gaji_bersih' => 8800000,
        ]);

        $detail = PayrollDetail::create([
            'id_payroll' => $payroll->id,
            'id_karyawan' => $karyawanA->id,
            'gaji_pokok' => 8000000,
            'total_tunjangan' => 1000000,
            'honorarium' => 0,
            'lembur' => 0,
            'total_potongan' => 200000,
            'gaji_bersih' => 8800000,
            'rincian_komponen' => [
                ['nama' => 'Gaji Pokok', 'tipe' => 'pendapatan', 'nilai' => 8000000],
            ],
        ]);

        $this->actingAs($userA, 'sanctum')
            ->get("/api/v1/ess/payslips/{$detail->id}/pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($userB, 'sanctum')
            ->get("/api/v1/ess/payslips/{$detail->id}/pdf")
            ->assertForbidden();
    }

    public function test_mobile_user_can_reset_pin_using_account_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('MySecurePass123!'),
            'pin' => Hash::make('111111'),
            'must_change_pin' => true,
        ]);
        $user->assignRole(RoleEnum::STAF->value);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pin/reset', [
                'current_password' => 'WrongPass',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertStatus(422);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/pin/reset', [
                'current_password' => 'MySecurePass123!',
                'pin' => '654321',
                'pin_confirmation' => '654321',
            ])
            ->assertOk();

        $user->refresh();
        $this->assertFalse($user->must_change_pin);
        $this->assertTrue(Hash::check('654321', $user->pin));
    }

    public function test_reimbursements_api_returns_unified_pengajuan_with_payment_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::STAF->value);
        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Siti Reimbursement',
            'nomor_induk_karyawan' => 'EMP-03',
            'jenis_kelamin' => 'P',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staff',
        ]);

        $pengajuan = PengajuanHeader::create([
            'nomor_pengajuan' => 'PJ-202611-0099',
            'id_pengaju' => $karyawan->id,
            'id_departemen' => $this->departemen->id,
            'tgl_pengajuan' => '2026-11-18',
            'judul_pengajuan' => 'Reimbursement Transport Liputan',
            'tipe_pengajuan' => PengajuanType::REIMBURSE,
            'total_nominal_diajukan' => 350000,
            'status_global' => PengajuanStatus::PAID,
        ]);

        PengajuanPembayaran::create([
            'id_pengajuan' => $pengajuan->id,
            'tgl_bayar' => '2026-11-20',
            'nominal_bayar' => 350000,
            'catatan' => 'Pencairan reimbursement media',
            'created_by' => $this->adminUser->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ess/reimbursements')
            ->assertOk()
            ->assertJsonPath('data.0.nomor_reimbursement', 'PJ-202611-0099')
            ->assertJsonPath('data.0.payment_status', 'Paid')
            ->assertJsonPath('data.0.paid_at', '2026-11-20');
    }

    public function test_mobile_approver_can_delegate_and_delegate_can_approve(): void
    {
        $staffUser = User::factory()->create();
        $staffUser->assignRole(RoleEnum::STAF->value);
        $staff = Karyawan::create([
            'user_id' => $staffUser->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Pemohon Cuti',
            'nomor_induk_karyawan' => 'EMP-10',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staff',
        ]);

        $managerUser = User::factory()->create(['pin' => Hash::make('123456')]);
        $managerUser->assignRole(RoleEnum::MANAJER_DEPARTEMEN->value);
        $manager = Karyawan::create([
            'user_id' => $managerUser->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Manajer Utama',
            'nomor_induk_karyawan' => 'EMP-11',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manajer',
        ]);

        $delegateUser = User::factory()->create(['pin' => Hash::make('654321')]);
        $delegateUser->assignRole(RoleEnum::MANAJER_DEPARTEMEN->value);
        Karyawan::create([
            'user_id' => $delegateUser->id,
            'id_departemen' => $this->departemen->id,
            'nama_lengkap' => 'Pengganti Sementara',
            'nomor_induk_karyawan' => 'EMP-12',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manajer',
        ]);

        ApprovalRule::create([
            'id_departemen' => $this->departemen->id,
            'id_karyawan_approver' => $manager->id,
            'tipe' => 'Cuti',
            'level_order' => 1,
            'label_aksi' => 'Approve Manajer',
            'min_amount' => 0,
        ]);

        $jenisCuti = JenisCuti::create(['nama_cuti' => 'Tahunan', 'kuota_default' => 12]);
        SaldoCuti::create([
            'id_karyawan' => $staff->id,
            'id_jenis_cuti' => $jenisCuti->id,
            'tahun_periode' => date('Y'),
            'saldo_awal' => 12,
            'saldo_terpakai' => 0,
            'saldo_akhir' => 12,
        ]);

        $cuti = PengajuanCuti::create([
            'id_karyawan' => $staff->id,
            'id_jenis_cuti' => $jenisCuti->id,
            'tgl_mulai' => now()->addDays(2)->format('Y-m-d'),
            'tgl_selesai' => now()->addDays(3)->format('Y-m-d'),
            'jumlah_hari' => 2,
            'alasan' => 'Urusan Keluarga',
            'status' => 'Pending',
        ]);
        app(ApprovalService::class)->initApproval($cuti);

        $process = ApprovalProcess::forDocument('Cuti', $cuti->id)->firstOrFail();

        // Manager creates delegation to delegateUser
        $createRes = $this->actingAs($managerUser, 'sanctum')
            ->postJson('/api/v1/approvals/delegations', [
                'delegate_user_id' => $delegateUser->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'reason' => 'Sedang Dinas Luar',
            ])
            ->assertCreated();

        $delegationId = $createRes->json('data.id');
        $this->assertTrue(ApprovalDelegation::findOrFail($delegationId)->is_active);

        // Delegate sees manager's pending approval in inbox and approves it with delegate PIN
        $this->actingAs($delegateUser, 'sanctum')
            ->getJson('/api/v1/approvals')
            ->assertOk()
            ->assertJsonPath('data.0.id', $process->id);

        $this->actingAs($delegateUser, 'sanctum')
            ->postJson("/api/v1/approvals/{$process->id}/action", [
                'status' => 'Approved',
                'pin' => '654321',
                'catatan' => 'Disetujui sebagai delegasi',
            ])
            ->assertOk();

        $this->assertEquals('Approved', $cuti->fresh()->status);

        // Manager revokes delegation
        $this->actingAs($managerUser, 'sanctum')
            ->deleteJson("/api/v1/approvals/delegations/{$delegationId}")
            ->assertOk();

        $this->assertFalse(ApprovalDelegation::findOrFail($delegationId)->is_active);
    }

    public function test_admin_can_send_multi_user_info_only_and_blast_all_open_page_push_messages(): void
    {
        $user1 = User::factory()->create();
        $user1->assignRole(RoleEnum::STAF->value);
        $user2 = User::factory()->create();
        $user2->assignRole(RoleEnum::STAF->value);
        $user3 = User::factory()->create();
        $user3->assignRole(RoleEnum::MANAJER_DEPARTEMEN->value);

        // 1. Multi-user with info_only (hanya notifikasi / popup pesan tanpa pindah halaman)
        $this->actingAs($this->adminUser)
            ->post(route('admin.settings.test-notification'), [
                'target_mode' => 'multi_user',
                'target_user_ids' => [$user1->id, $user2->id],
                'type' => 'announcement',
                'click_action' => 'info_only',
                'title' => 'Pengumuman Libur Resmi',
                'message' => 'Kantor operasional tutup pada hari Jumat.',
                'link' => '/should-be-ignored',
            ])
            ->assertRedirect();

        $this->assertEquals(1, $user1->notifications()->count());
        $this->assertEquals(1, $user2->notifications()->count());
        $this->assertEquals(0, $user3->notifications()->count());

        $notif1 = $user1->notifications()->firstOrFail();
        $this->assertNull($notif1->data['link']);
        $this->assertEquals('info_only', $notif1->data['click_action']);

        // Web markAsRead on info_only stays back (no deep-link redirect)
        $this->actingAs($user1)
            ->get(route('admin.notifications.read', $notif1->id))
            ->assertRedirect();
        $this->assertNotNull($notif1->fresh()->read_at);

        // 2. Blast all with open_page + FCM HTTP v1 enabled
        $keyResource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($keyResource);
        openssl_pkey_export($keyResource, $privateKeyPem);

        $saJson = json_encode([
            'type' => 'service_account',
            'project_id' => 'persija-blast-test',
            'private_key' => $privateKeyPem,
            'client_email' => 'blast@persija-blast-test.iam.gserviceaccount.com',
        ]);

        $configService = app(SystemConfigService::class);
        Setting::updateOrCreate(['key' => 'fcm_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'fcm_project_id'], ['value' => 'persija-blast-test']);
        Setting::updateOrCreate(['key' => 'fcm_service_account_json'], ['value' => $configService->encryptValue($saJson)]);
        $configService->clearCache();

        UserDeviceToken::create([
            'user_id' => $user3->id,
            'device_token' => 'blast-device-token-1',
            'device_type' => 'android',
            'is_active' => true,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'mock-blast-token'], 200),
            'https://fcm.googleapis.com/v1/projects/persija-blast-test/messages:send' => Http::response(['name' => 'ok'], 200),
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('admin.settings.test-notification'), [
                'target_mode' => 'blast_all',
                'type' => 'ess',
                'click_action' => 'open_page',
                'title' => 'Slip Gaji November Tersedia',
                'message' => 'Silakan cek rincian gaji Anda.',
                'link' => '/ess?tab=payslip',
            ])
            ->assertRedirect();

        $this->assertEquals(1, $user3->notifications()->count());
        $notif3 = $user3->notifications()->firstOrFail();
        $this->assertEquals('/ess?tab=payslip', $notif3->data['link']);
        $this->assertEquals('open_page', $notif3->data['click_action']);

        // Web markAsRead on open_page redirects to target link
        $this->actingAs($user3)
            ->get(route('admin.notifications.read', $notif3->id))
            ->assertRedirect('/ess?tab=payslip');

        Http::assertSentCount(2);
    }
}
