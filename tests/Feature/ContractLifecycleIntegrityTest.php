<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\KaryawanContract;
use App\Models\KaryawanDokumen;
use App\Models\RiwayatKarir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractLifecycleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $departemen;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Role::firstOrCreate(['name' => 'Super Admin']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');

        $this->departemen = Departemen::create([
            'nama_departemen' => 'Information Technology',
            'kode_departemen' => 'IT',
        ]);
    }

    public function test_first_contract_issuance_creates_initial_career_and_v1_contract()
    {
        $karyawan = Karyawan::create([
            'nomor_induk_karyawan' => 'TEST-001',
            'nama_lengkap' => 'Budi Santoso',
            'id_departemen' => $this->departemen->id,
            'jabatan' => 'Staff IT',
            'status_karyawan' => 'Kontrak',
            'gaji_pokok' => 5000000,
            'jenis_kelamin' => 'L',
        ]);

        $this->assertEquals(0, $karyawan->contracts()->count());
        $this->assertEquals(0, $karyawan->riwayatKarir()->count());

        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.contract-details.update', $karyawan->id), [
            'nomor_kontrak' => '001/PKWT/HRD/2026',
            'contract_type' => 'PKWT',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'durasi_probation_bulan' => 0,
            'notice_period_hari' => 30,
            'jam_kerja_mingguan' => 40,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 1. Riwayat Karir record created for 'Pengangkatan Awal'
        $this->assertEquals(1, $karyawan->riwayatKarir()->count());
        $career = $karyawan->riwayatKarir()->first();
        $this->assertEquals('Pengangkatan Awal', $career->tipe_peristiwa);
        $this->assertEquals('2026-01-01', $career->tanggal_efektif->toDateString());
        $this->assertEquals('2026-12-31', $career->tanggal_berakhir_kontrak->toDateString());

        // 2. Contract record created with v1 and active status
        $this->assertEquals(1, $karyawan->contracts()->count());
        $contract = $karyawan->contracts()->first();
        $this->assertEquals(1, $contract->version_number);
        $this->assertEquals('active', $contract->status);
        $this->assertEquals('001/PKWT/HRD/2026', $contract->nomor_kontrak);

        // 3. Document record created for archived PDF
        $this->assertEquals(1, KaryawanDokumen::where('id_karyawan', $karyawan->id)->count());
        $doc = KaryawanDokumen::where('id_karyawan', $karyawan->id)->first();
        $this->assertEquals('kontrak', $doc->kategori_dokumen);
        $this->assertStringContainsString('v1', $doc->judul_dokumen);
    }

    public function test_contract_extension_creates_new_version_and_perpanjangan_kontrak()
    {
        $karyawan = Karyawan::create([
            'nomor_induk_karyawan' => 'TEST-002',
            'nama_lengkap' => 'Siti Rahma',
            'id_departemen' => $this->departemen->id,
            'jabatan' => 'Media Officer',
            'status_karyawan' => 'Kontrak',
            'gaji_pokok' => 6000000,
            'jenis_kelamin' => 'P',
        ]);

        // Issue initial v1
        $this->actingAs($this->admin)->post(route('admin.karyawan.contract-details.update', $karyawan->id), [
            'nomor_kontrak' => '002/PKWT/HRD/2026',
            'contract_type' => 'PKWT',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-12-31',
            'durasi_probation_bulan' => 0,
            'notice_period_hari' => 30,
            'jam_kerja_mingguan' => 40,
        ]);

        $this->assertEquals(1, $karyawan->contracts()->count());

        // Extend contract with is_new_version = true
        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.contract-details.update', $karyawan->id), [
            'nomor_kontrak' => '002/PKWT-EXT/HRD/2027',
            'contract_type' => 'PKWT',
            'tanggal_mulai' => '2027-01-01',
            'tanggal_selesai' => '2027-12-31',
            'durasi_probation_bulan' => 0,
            'notice_period_hari' => 30,
            'jam_kerja_mingguan' => 40,
            'is_new_version' => 1,
            'catatan_perubahan' => 'Perpanjangan kontrak tahun ke-2',
        ]);

        $response->assertSessionHasNoErrors();

        // 1. Two career events: Pengangkatan Awal & Perpanjangan Kontrak
        $this->assertEquals(2, $karyawan->riwayatKarir()->count());
        $latestCareer = $karyawan->riwayatKarir()->orderBy('tanggal_efektif', 'desc')->first();
        $this->assertEquals('Perpanjangan Kontrak', $latestCareer->tipe_peristiwa);
        $this->assertEquals('2027-01-01', $latestCareer->tanggal_efektif->toDateString());

        $this->assertEquals(2, $karyawan->contracts()->count());
        $v1 = $karyawan->contracts()->where('version_number', 1)->first();
        $v2 = $karyawan->contracts()->where('version_number', 2)->first();
        $this->assertEquals('superseded', $v1->status);
        $this->assertEquals('active', $v2->status);

        // 3. Employee record has new contract end date and preserved join date
        $karyawan->refresh();
        $this->assertEquals('2027-12-31', $karyawan->tanggal_berakhir_kontrak->toDateString());
        $this->assertEquals('2026-01-01', substr((string) $karyawan->tgl_bergabung, 0, 10));
    }

    public function test_promote_to_permanent_pkwtt_during_extension()
    {
        $karyawan = Karyawan::create([
            'nomor_induk_karyawan' => 'TEST-003',
            'nama_lengkap' => 'Ahmad Fauzi',
            'id_departemen' => $this->departemen->id,
            'jabatan' => 'Senior Developer',
            'status_karyawan' => 'Kontrak',
            'contract_type' => 'PKWT',
            'tgl_bergabung' => '2025-01-01',
            'gaji_pokok' => 9000000,
            'jenis_kelamin' => 'L',
        ]);

        // Issue initial v1
        $this->actingAs($this->admin)->post(route('admin.karyawan.contract-details.update', $karyawan->id), [
            'nomor_kontrak' => '003/PKWT/HRD/2025',
            'contract_type' => 'PKWT',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-12-31',
            'durasi_probation_bulan' => 0,
            'notice_period_hari' => 30,
            'jam_kerja_mingguan' => 40,
        ]);

        // Promote to PKWTT (Permanent)
        $response = $this->actingAs($this->admin)->post(route('admin.karyawan.contract-details.update', $karyawan->id), [
            'nomor_kontrak' => '003/PKWTT/HRD/2026',
            'contract_type' => 'PKWTT',
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => null,
            'durasi_probation_bulan' => 0,
            'notice_period_hari' => 30,
            'jam_kerja_mingguan' => 40,
            'is_new_version' => 1,
            'catatan_perubahan' => 'Pengangkatan Menjadi Karyawan Tetap (PKWTT v2)',
        ]);

        $response->assertSessionHasNoErrors();

        $karyawan->refresh();
        $this->assertEquals('Tetap', $karyawan->status_karyawan);
        $this->assertEquals('PKWTT', $karyawan->contract_type);
        $this->assertNull($karyawan->tanggal_berakhir_kontrak);
        $this->assertEquals('2025-01-01', substr((string) $karyawan->tgl_bergabung, 0, 10));

        // Latest career event must be 'Pengangkatan Tetap'
        $latestCareer = $karyawan->riwayatKarir()->orderBy('tanggal_efektif', 'desc')->first();
        $this->assertEquals('Pengangkatan Tetap', $latestCareer->tipe_peristiwa);
        $this->assertNull($latestCareer->tanggal_berakhir_kontrak);

        // Contract version 2 created as PKWTT
        $v2 = $karyawan->contracts()->where('version_number', 2)->first();
        $this->assertNotNull($v2);
        $this->assertEquals('PKWTT', $v2->contract_type);
        $this->assertNull($v2->tanggal_selesai);
    }
}
