<?php

namespace Tests\Feature;

use App\Models\ContractTemplate;
use App\Models\Departemen;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Super Admin');
    }

    public function test_templates_are_auto_seeded_when_database_table_is_empty(): void
    {
        // Ensure table is empty
        ContractTemplate::truncate();
        $this->assertEquals(0, ContractTemplate::count());

        // Visiting index route auto-seeds default templates
        $response = $this->actingAs($this->admin)->get(route('admin.contract-templates.index'));
        $response->assertOk();

        // 3 default templates should now exist (PKWT, PKWTT, Professional)
        $this->assertGreaterThanOrEqual(3, ContractTemplate::count());
        $this->assertDatabaseHas('tbl_contract_templates', ['contract_type' => 'PKWT']);
        $this->assertDatabaseHas('tbl_contract_templates', ['contract_type' => 'PKWTT']);
        $this->assertDatabaseHas('tbl_contract_templates', ['contract_type' => 'Professional']);
    }

    public function test_contract_template_can_be_updated(): void
    {
        // Auto-seed
        $this->actingAs($this->admin)->get(route('admin.contract-templates.index'));
        $template = ContractTemplate::where('contract_type', 'PKWT')->firstOrFail();

        $response = $this->actingAs($this->admin)->put(route('admin.contract-templates.update', $template->id), [
            'nama_template' => 'Template PKWT Custom 2026',
            'judul_surat'   => 'SURAT PERJANJIAN KERJA WAKTU TERTENTU RESMI',
            'konten'        => '<p>Isi klausul yang telah diperbarui oleh HRD</p>',
            'is_active'     => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tbl_contract_templates', [
            'id'            => $template->id,
            'nama_template' => 'Template PKWT Custom 2026',
            'judul_surat'   => 'SURAT PERJANJIAN KERJA WAKTU TERTENTU RESMI',
        ]);
    }

    public function test_contract_template_can_be_reset(): void
    {
        // Auto-seed
        $this->actingAs($this->admin)->get(route('admin.contract-templates.index'));
        $template = ContractTemplate::where('contract_type', 'PKWT')->firstOrFail();

        // Modify it first
        $template->update(['nama_template' => 'Modified Name']);

        // Call reset
        $response = $this->actingAs($this->admin)->post(route('admin.contract-templates.reset', $template->id));
        $response->assertRedirect();

        // Check it has been restored
        $refreshed = $template->fresh();
        $this->assertStringContainsString('Template Standar PKWT', $refreshed->nama_template);
    }

    public function test_karyawan_contract_preview_auto_seeds_when_empty(): void
    {
        ContractTemplate::truncate();
        $this->assertEquals(0, ContractTemplate::count());

        $dept = Departemen::create([
            'nama_departemen' => 'Operasional',
            'kode_departemen' => 'OPS',
        ]);

        $karyawan = Karyawan::create([
            'nomor_induk_karyawan' => 'TEST-TMPL-01',
            'nama_lengkap' => 'Ahmad Template',
            'id_departemen' => $dept->id,
            'jabatan' => 'Staff Operasional',
            'status_karyawan' => 'Kontrak',
            'gaji_pokok' => 5000000,
            'contract_type' => 'PKWT',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.karyawan.contract-preview', $karyawan->id));
        $response->assertOk();
        $data = $response->json();

        // Master content should not be empty, and templates should be auto-seeded
        $this->assertNotEmpty($data['master_content']);
        $this->assertGreaterThanOrEqual(3, ContractTemplate::count());
    }
}
