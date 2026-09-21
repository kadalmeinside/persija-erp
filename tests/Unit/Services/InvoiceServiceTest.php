<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InvoiceService;
use App\Services\RevenueBudgetService;
use App\Models\InvoiceHeader;
use App\Models\InvoiceDetail;
use App\Models\Pelanggan;
use App\Models\Departemen;
use App\Models\AkunGl;
use App\Models\TaxType;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $revenueBudgetService = $this->app->make(RevenueBudgetService::class);
        $this->invoiceService = new InvoiceService($revenueBudgetService);

        // Seeding required base data
        $this->dept = Departemen::create([
            'nama_departemen' => 'Sales Dept',
            'kode_departemen' => 'SLS'
        ]);

        $this->pelanggan = Pelanggan::create([
            'nama_pelanggan' => 'PT Makmur Jaya',
            'kode_pelanggan' => 'CUST-001',
            'email' => 'makmur@example.com'
        ]);

        $this->akunPendapatan = AkunGl::create([
            'kode_akun' => '4-1001',
            'nama_akun' => 'Pendapatan Jasa',
            'tipe_akun' => 'Pendapatan',
            'is_active' => true
        ]);

        $this->akunPiutang = AkunGl::create([
            'kode_akun' => '1-2001',
            'nama_akun' => 'Piutang Usaha',
            'tipe_akun' => 'Aset',
            'is_active' => true
        ]);

        $this->akunPPN = AkunGl::create([
            'kode_akun' => '2-1001',
            'nama_akun' => 'Utang PPN',
            'tipe_akun' => 'Utang',
            'is_active' => true
        ]);

        $this->taxPpn = TaxType::create([
            'kode_pajak' => 'PPN-11',
            'nama_pajak' => 'PPN 11%',
            'rate' => 11.00,
            'tipe' => 'Keluaran',
            'id_akun_gl' => $this->akunPPN->id,
            'is_active' => true,
            'transaction_type' => 'Invoice'
        ]);
        
        // Mock Auth User to bypass created_by nullable issues
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
    }

    public function test_create_invoice_calculates_subtotal_and_taxes_correctly()
    {
        $data = [
            'id_departemen' => $this->dept->id,
            'id_pelanggan' => $this->pelanggan->id,
            'tgl_invoice' => now()->format('Y-m-d'),
            'tgl_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'id_tax_ppn' => $this->taxPpn->id,
            'items' => [
                [
                    'deskripsi_item' => 'Jasa A',
                    'kuantitas' => 2,
                    'harga_satuan' => 1000000,
                    'id_akun_pendapatan' => $this->akunPendapatan->id
                ],
                [
                    'deskripsi_item' => 'Jasa B',
                    'kuantitas' => 1,
                    'harga_satuan' => 500000,
                    'id_akun_pendapatan' => $this->akunPendapatan->id
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($data);

        // Assert Header
        $this->assertEquals(InvoiceStatus::Draft->value, $invoice->status->value);
        $this->assertEquals(2500000, $invoice->subtotal);    // (2*1M) + (1*500k)
        $this->assertEquals(11, $invoice->ppn_rate);
        $this->assertEquals(275000, $invoice->ppn_amount);   // 11% of 2.5M
        $this->assertEquals(2775000, $invoice->total_tagihan); // 2.5M + 275k
        $this->assertEquals(2775000, $invoice->sisa_tagihan);

        // Assert Detail
        $this->assertCount(2, $invoice->detail);
        $this->assertEquals(2000000, $invoice->detail[0]->total_harga);
        $this->assertEquals(500000, $invoice->detail[1]->total_harga);
    }

    public function test_post_gl_after_approval_creates_correct_journal_entries()
    {
        $data = [
            'id_departemen' => $this->dept->id,
            'id_pelanggan' => $this->pelanggan->id,
            'tgl_invoice' => now()->format('Y-m-d'),
            'tgl_jatuh_tempo' => now()->addDays(30)->format('Y-m-d'),
            'id_tax_ppn' => $this->taxPpn->id,
            'items' => [
                [
                    'deskripsi_item' => 'Jasa A',
                    'kuantitas' => 2,
                    'harga_satuan' => 1000000, // Total 2M
                    'id_akun_pendapatan' => $this->akunPendapatan->id
                ]
            ]
        ];

        $invoice = $this->invoiceService->createInvoice($data);

        // Call logic that simulates final approval GL POST
        $this->invoiceService->postGLAfterApproval($invoice);

        // Refresh model from DB
        $invoice->refresh();

        // Check journal has been created
        $journal = \App\Models\JurnalHeader::with('detail')->where('sumber_modul', 'AR')->where('id_referensi_sumber', $invoice->id)->first();
        $this->assertNotNull($journal);
        $this->assertEquals('AR', $journal->sumber_modul);
        
        // Assert Details (Debit Piutang 2.22M, Kredit Pendapatan 2M, Kredit Utang PPN 220k)
        // Piutang
        $piutangDetails = $journal->detail->where('debit', '>', 0);
        $this->assertCount(1, $piutangDetails);
        $this->assertEquals($this->akunPiutang->id, $piutangDetails->first()->id_akun);
        $this->assertEquals(2220000, $piutangDetails->first()->debit);

        // Kredit (Pendapatan & PPN)
        $kreditDetails = $journal->detail->where('kredit', '>', 0);
        $this->assertCount(2, $kreditDetails);
        
        $kreditPendapatan = $kreditDetails->where('id_akun', $this->akunPendapatan->id)->first();
        $this->assertNotNull($kreditPendapatan);
        $this->assertEquals(2000000, $kreditPendapatan->kredit);

        $kreditPpn = $kreditDetails->where('id_akun', $this->akunPPN->id)->first();
        $this->assertNotNull($kreditPpn);
        $this->assertEquals(220000, $kreditPpn->kredit);
    }
}
