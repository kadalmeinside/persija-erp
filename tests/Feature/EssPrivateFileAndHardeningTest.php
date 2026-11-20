<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ContractTemplate;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\KaryawanDokumen;
use App\Models\RiwayatKarir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class EssPrivateFileAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (Role::cases() as $role) {
            SpatieRole::firstOrCreate(['name' => $role->value, 'guard_name' => 'web']);
        }
    }

    public function test_ess_kontrak_riwayat_and_dokumen_endpoints_return_valid_data_without_sql_or_url_corruption(): void
    {
        Storage::fake('local');

        $dept = Departemen::create(['kode_departemen' => 'MKT', 'nama_departemen' => 'Marketing']);
        $user = User::factory()->create();
        $user->assignRole(Role::KARYAWAN->value);

        $karyawan = Karyawan::create([
            'user_id' => $user->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-501',
            'nama_lengkap' => 'Rizky Pratama',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Kontrak',
            'jabatan' => 'Staff Marketing',
        ]);

        RiwayatKarir::create([
            'id_karyawan' => $karyawan->id,
            'id_departemen' => $dept->id,
            'tipe_peristiwa' => 'Pengangkatan Awal',
            'tanggal_efektif' => '2026-01-01',
            'tanggal_berakhir_kontrak' => '2026-12-31',
            'jabatan' => 'Staff Marketing',
            'status_karyawan' => 'Kontrak',
        ]);

        $path = "karyawan_dokumen/{$karyawan->id}/surat_tugas.pdf";
        Storage::disk('local')->put($path, 'PDFDATA');
        $doc = KaryawanDokumen::create([
            'id_karyawan' => $karyawan->id,
            'kategori_dokumen' => 'Kontrak',
            'judul_dokumen' => 'Surat Kontrak Kerja',
            'file_path' => $path,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ess/kontrak-info')
            ->assertOk()
            ->assertJsonPath('data.tanggal_mulai', '2026-01-01')
            ->assertJsonPath('data.departemen', 'Marketing');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ess/riwayat-karir')
            ->assertOk()
            ->assertJsonPath('data.0.departemen', 'Marketing')
            ->assertJsonPath('data.0.jenis_perubahan', 'Pengangkatan Awal')
            ->assertJsonPath('data.0.tanggal_mulai_kontrak', '2026-01-01');

        $dokRes = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/ess/dokumen')
            ->assertOk()
            ->assertJsonPath('data.0.nama_dokumen', 'Surat Kontrak Kerja')
            ->assertJsonPath('data.0.kategori', 'Kontrak');

        $this->assertStringContainsString("/api/v1/ess/dokumen/{$doc->id}/download", $dokRes->json('data.0.file_path'));

        $this->actingAs($user, 'sanctum')
            ->get("/api/v1/ess/dokumen/{$doc->id}/download")
            ->assertOk();
    }

    public function test_private_file_controller_enforces_ownership_authorization(): void
    {
        Storage::fake('local');

        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);

        $owner = User::factory()->create();
        $owner->assignRole(Role::STAF->value);
        $ownerKaryawan = Karyawan::create([
            'user_id' => $owner->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-601',
            'nama_lengkap' => 'Owner',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $other = User::factory()->create();
        $other->assignRole(Role::STAF->value);
        Karyawan::create([
            'user_id' => $other->id,
            'id_departemen' => $dept->id,
            'nomor_induk_karyawan' => 'EMP-602',
            'nama_lengkap' => 'Other',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Staf',
        ]);

        $path = "karyawan_dokumen/{$ownerKaryawan->id}/ktp.pdf";
        Storage::disk('local')->put($path, 'PRIVATE_KTP');
        $doc = KaryawanDokumen::create([
            'id_karyawan' => $ownerKaryawan->id,
            'kategori_dokumen' => 'Identitas',
            'judul_dokumen' => 'KTP',
            'file_path' => $path,
        ]);

        $this->actingAs($owner)->get(route('admin.files.dokumen', $doc->id))->assertOk();
        $this->actingAs($other)->get(route('admin.files.dokumen', $doc->id))->assertForbidden();
    }

    public function test_contract_template_escapes_placeholders_and_strips_scripts_and_freelance_is_isolated(): void
    {
        $dept = Departemen::create(['kode_departemen' => 'OPS', 'nama_departemen' => 'Operasional']);
        $karyawan = new Karyawan([
            'nama_lengkap' => '<script>alert(1)</script>Budi',
            'jabatan' => '<b>Pelatih</b>',
        ]);
        $karyawan->setRelation('departemen', $dept);

        $html = ContractTemplate::parseContent('<p>{NAMA_KARYAWAN}</p><script>evil()</script>', $karyawan, [
            'name' => 'PT Persija',
            'address' => 'Jakarta',
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;Budi', $html);
        $this->assertNotNull(config('sanctum.expiration'));

        $freelance = User::factory()->create();
        $freelance->assignRole(Role::FREELANCE->value);
        $this->actingAs($freelance)->get(route('admin.pengajuan.my-requests'))->assertForbidden();
    }
}
