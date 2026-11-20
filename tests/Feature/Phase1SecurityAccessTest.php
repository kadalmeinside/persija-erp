<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use App\Enums\Role;
use App\Models\Departemen;
use App\Models\JenisCuti;
use App\Models\Karyawan;
use App\Models\KoreksiAbsensi;
use App\Models\PengajuanCuti;
use App\Models\PengajuanHeader;
use App\Models\SaldoCuti;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class Phase1SecurityAccessTest extends TestCase
{
    use RefreshDatabase;

    private Departemen $dept;
    private User $stafA;
    private Karyawan $karyawanA;
    private User $stafB;
    private Karyawan $karyawanB;
    private User $hrUser;
    private Karyawan $hrKaryawan;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::cases() as $role) {
            SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }

        $this->dept = Departemen::create([
            'kode_departemen' => 'OPS',
            'nama_departemen' => 'Operasional',
        ]);

        $this->stafA = User::factory()->create(['pin' => Hash::make('112233')]);
        $this->stafA->assignRole(Role::STAF->value);
        $this->karyawanA = Karyawan::create([
            'user_id' => $this->stafA->id,
            'id_departemen' => $this->dept->id,
            'nomor_induk_karyawan' => 'EMP-001',
            'nama_lengkap' => 'Staf Satu',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $this->stafB = User::factory()->create(['pin' => Hash::make('112233')]);
        $this->stafB->assignRole(Role::STAF->value);
        $this->karyawanB = Karyawan::create([
            'user_id' => $this->stafB->id,
            'id_departemen' => $this->dept->id,
            'nomor_induk_karyawan' => 'EMP-002',
            'nama_lengkap' => 'Staf Dua',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $this->hrUser = User::factory()->create(['pin' => Hash::make('112233')]);
        $this->hrUser->assignRole(Role::HR_MANAGER->value);
        $this->hrKaryawan = Karyawan::create([
            'user_id' => $this->hrUser->id,
            'id_departemen' => $this->dept->id,
            'nomor_induk_karyawan' => 'HR-001',
            'nama_lengkap' => 'HR Manager',
            'jenis_kelamin' => 'P',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'HR Manager',
        ]);
    }

    public function test_employee_cannot_self_approve_attendance_correction_on_web_or_api(): void
    {
        $koreksi = KoreksiAbsensi::create([
            'id_karyawan' => $this->karyawanA->id,
            'tanggal' => now()->toDateString(),
            'tipe_koreksi' => 'Lupa Check In',
            'jam_koreksi_masuk' => '08:30:00',
            'alasan' => 'Lupa absen',
            'status' => 'Pending',
        ]);

        $this->actingAs($this->hrUser)
            ->from('/admin/absensi');

        // Give Staf A HR role to prove SoD blocks even HR self-approval
        $this->stafA->assignRole(Role::HR_STAFF->value);

        $webRes = $this->actingAs($this->stafA)
            ->post(route('admin.absensi.koreksi.action', $koreksi->id), [
                'action' => 'approve',
            ]);
        $webRes->assertForbidden();

        $apiRes = $this->actingAs($this->stafA, 'sanctum')
            ->postJson("/api/v1/ess/attendance-correction/{$koreksi->id}/action", [
                'action' => 'approve',
            ]);
        $apiRes->assertForbidden();

        $this->assertEquals('Pending', $koreksi->fresh()->status);
    }

    public function test_regular_staf_cannot_access_restricted_ess_admin_or_payment_routes(): void
    {
        $jenis = JenisCuti::create([
            'nama_cuti' => 'Tahunan',
            'kode_cuti' => 'CT',
            'kuota_default' => 12,
            'is_unlimited' => false,
            'khusus_perempuan' => false,
        ]);

        $saldo = SaldoCuti::create([
            'id_karyawan' => $this->karyawanA->id,
            'id_jenis_cuti' => $jenis->id,
            'tahun_periode' => (int) date('Y'),
            'saldo_awal' => 12,
            'saldo_terpakai' => 0,
            'saldo_akhir' => 12,
        ]);

        $this->actingAs($this->stafA)->get(route('admin.cuti.management'))->assertForbidden();
        $this->actingAs($this->stafA)->post(route('admin.cuti.generate'), [
            'id_jenis_cuti' => $jenis->id,
            'tahun' => (int) date('Y'),
        ])->assertForbidden();
        $this->actingAs($this->stafA)->put(route('admin.cuti.update-balance', $saldo->id), [
            'saldo_awal' => 99,
            'saldo_terpakai' => 0,
        ])->assertForbidden();

        $this->actingAs($this->stafA)->get(route('admin.pengajuan.payment-schedule'))->assertForbidden();
        $this->actingAs($this->stafA)->post(route('admin.calendar.share-settings'), [
            'enabled' => true,
            'show_details' => true,
        ])->assertForbidden();
        $this->actingAs($this->stafA)->post(route('admin.hari-libur.store'), [
            'tanggal' => now()->toDateString(),
            'keterangan' => 'Libur',
        ])->assertForbidden();
        $this->actingAs($this->stafA)->postJson(route('admin.pengajuan.storeVendor'), [
            'nama_vendor' => 'Fake Vendor',
            'nama_bank' => 'BCA',
            'nomor_rekening' => '123456',
            'atas_nama_rekening' => 'Fake',
        ])->assertForbidden();
    }

    public function test_staf_cannot_view_other_employees_cuti_or_pengajuan_idor(): void
    {
        $jenis = JenisCuti::create([
            'nama_cuti' => 'Tahunan',
            'kode_cuti' => 'CT',
            'kuota_default' => 12,
            'is_unlimited' => false,
            'khusus_perempuan' => false,
        ]);

        $cutiB = PengajuanCuti::create([
            'id_karyawan' => $this->karyawanB->id,
            'id_jenis_cuti' => $jenis->id,
            'tgl_mulai' => now()->addDay()->toDateString(),
            'tgl_selesai' => now()->addDays(2)->toDateString(),
            'jumlah_hari' => 2,
            'alasan' => 'Urusan keluarga',
            'status_global' => 'Pending Approval',
        ]);

        $this->actingAs($this->stafA)->get(route('admin.cuti.show', $cutiB->id))->assertForbidden();
        $this->actingAs($this->stafA)->get(route('admin.cuti.print', $cutiB->id))->assertForbidden();

        $pengajuanB = PengajuanHeader::create([
            'nomor_pengajuan' => 'PJ-2026-0001',
            'id_pengaju' => $this->karyawanB->id,
            'id_departemen' => $this->dept->id,
            'tgl_pengajuan' => now()->toDateString(),
            'judul_pengajuan' => 'Rahasia Staf B',
            'tipe_pengajuan' => PengajuanType::LANGSUNG,
            'total_nominal_diajukan' => 500000,
            'metode_pembayaran' => PaymentMethod::TRANSFER,
            'status_global' => PengajuanStatus::PENDING_APPROVAL,
        ]);

        $this->actingAs($this->stafA)->get(route('admin.pengajuan.show', $pengajuanB->id))->assertForbidden();
        $this->actingAs($this->stafA)->get(route('admin.pengajuan.edit', $pengajuanB->id))->assertForbidden();
        $this->actingAs($this->stafA)->delete(route('admin.pengajuan.destroy', $pengajuanB->id))->assertForbidden();
        $this->actingAs($this->stafA)->patch(route('admin.pengajuan.cancel', $pengajuanB->id))->assertForbidden();
    }

    public function test_staf_cannot_assign_tasks_to_others_or_modify_unrelated_tasks(): void
    {
        $resAssign = $this->actingAs($this->stafA)->post(route('admin.tasks.store'), [
            'id_karyawan_assignee' => $this->karyawanB->id,
            'title' => 'Tugas Palsu',
            'priority' => 'High',
        ]);
        $resAssign->assertForbidden();

        $taskB = Task::create([
            'id_karyawan_creator' => $this->karyawanB->id,
            'id_karyawan_assignee' => $this->karyawanB->id,
            'id_departemen' => $this->dept->id,
            'title' => 'Tugas Milik B',
            'priority' => 'Medium',
            'status' => 'To Do',
        ]);

        $this->actingAs($this->stafA)->post(route('admin.tasks.updateStatus', $taskB->id), [
            'status' => 'Done',
        ])->assertForbidden();
        $this->actingAs($this->stafA)->delete(route('admin.tasks.destroy', $taskB->id))->assertForbidden();
        $this->actingAs($this->stafA)->post(route('admin.tasks.archive', $taskB->id))->assertForbidden();
    }
}
