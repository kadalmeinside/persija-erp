<?php

namespace Tests\Unit\Services;

use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_correct_format_for_pengajuan(): void
    {
        $nomor = DocumentNumberService::pengajuan();

        $this->assertMatchesRegularExpression(
            '/^PJ\/\d{6}\/\d{4}$/',
            $nomor,
            "Format harus PJ/YYYYMM/NNNN, dapat: $nomor"
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_generates_sequential_numbers(): void
    {
        $ym = now()->format('Ym');

        // Buat record minimal yang memenuhi FK constraint
        $dept = \App\Models\Departemen::create(['nama_departemen' => 'Dept Dummy']);
        $user = \App\Models\User::factory()->create();
        $kary = \App\Models\Karyawan::create([
            'user_id'              => $user->id,
            'nama_lengkap'         => 'Dummy',
            'id_departemen'        => $dept->id,
            'jabatan'              => 'Staf',
            'nomor_induk_karyawan' => 'DUM-001',
            'gaji_pokok'           => 0,
            'status_karyawan'      => 'Tetap',
            'tgl_bergabung'        => now(),
        ]);

        // Simulasi record pertama dengan nomor format baru
        \Illuminate\Support\Facades\DB::table('tbl_pengajuan_header')->insert([
            'nomor_pengajuan'        => "PJ/{$ym}/0001",
            'judul_pengajuan'        => 'Record Lama',
            'id_pengaju'             => $kary->id,
            'id_departemen'          => $dept->id,
            'tgl_pengajuan'          => now(),
            'tipe_pengajuan'         => 'OperasionalUmum',
            'total_nominal_diajukan' => 0,
            'status_global'          => 'Pending Approval',
            'metode_pembayaran'      => 'Cash',
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        // Generate berikutnya harus 0002
        $second = DocumentNumberService::pengajuan();

        $this->assertStringEndsWith('/0002', $second, 'Nomor setelah 0001 harus 0002');
    }

    /** @test */
    public function it_starts_from_0001_when_no_existing_records(): void
    {
        $nomor = DocumentNumberService::pengajuan();

        $this->assertStringEndsWith('/0001', $nomor, 'Record pertama harus berakhiran /0001');
    }

    /** @test */
    public function it_generates_different_prefixes_independently(): void
    {
        $pj   = DocumentNumberService::pengajuan();
        $loan = DocumentNumberService::pinjaman();
        $pay  = DocumentNumberService::payroll();

        // Ketiganya mulai dari 0001 karena prefix berbeda
        $this->assertStringEndsWith('/0001', $pj);
        $this->assertStringEndsWith('/0001', $loan);
        $this->assertStringEndsWith('/0001', $pay);
    }

    /** @test */
    public function it_uses_yearmonth_in_the_number(): void
    {
        $ym    = now()->format('Ym');
        $nomor = DocumentNumberService::pengajuan();

        $this->assertStringContainsString("/$ym/", $nomor, "Nomor harus mengandung YYYYMM saat ini ($ym)");
    }

    /** @test */
    public function it_can_override_yearmonth_for_testing(): void
    {
        $nomor = DocumentNumberService::pengajuan('202001');

        $this->assertStringContainsString('/202001/', $nomor);
    }

    /** @test */
    public function vendor_number_has_correct_format(): void
    {
        $kode = DocumentNumberService::vendor();

        $this->assertMatchesRegularExpression('/^V\/\d{6}\/\d{4}$/', $kode);
    }

    /** @test */
    public function customer_number_has_correct_format(): void
    {
        $kode = DocumentNumberService::customer();

        $this->assertMatchesRegularExpression('/^CUST\/\d{6}\/\d{4}$/', $kode);
    }
}
