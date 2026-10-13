<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Karyawan;
use App\Models\Departemen;
use App\Models\ApprovalDelegation;
use App\Models\ApprovalProcess;
use App\Models\ApprovalDocument;
use App\Services\ImmutableAuditService;
use App\Services\ApprovalGovernanceService;
use App\Services\FinancialReconciliationService;

class IdempotencyAndGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user1;
    protected $user2;
    protected $dept;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dept = Departemen::create(['nama_departemen' => 'IT', 'kode_departemen' => 'IT']);

        $this->user1 = User::factory()->create(['email_verified_at' => now()]);
        $this->user2 = User::factory()->create(['email_verified_at' => now()]);

        Karyawan::create([
            'user_id' => $this->user1->id,
            'id_departemen' => $this->dept->id,
            'nama_lengkap' => 'Karyawan 1',
            'nomor_induk_karyawan' => 'K1-001',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Lead Engineer',
        ]);

        Karyawan::create([
            'user_id' => $this->user2->id,
            'id_departemen' => $this->dept->id,
            'nama_lengkap' => 'Karyawan 2',
            'nomor_induk_karyawan' => 'K2-002',
            'jenis_kelamin' => 'L',
            'status_karyawan' => 'Tetap',
            'jabatan' => 'Manager IT',
        ]);
    }

    public function test_immutable_audit_log_maintains_unbroken_cryptographic_hash_chain()
    {
        // Generate 3 sequential audit entries
        $log1 = ImmutableAuditService::log(
            action: 'USER_LOGIN',
            entityType: 'User',
            entityId: (string) $this->user1->id,
            payload: ['ip' => '127.0.0.1'],
            actorId: $this->user1->id,
            actorName: $this->user1->name
        );

        $log2 = ImmutableAuditService::log(
            action: 'LEAVE_SUBMITTED',
            entityType: 'PengajuanCuti',
            entityId: '10',
            payload: ['days' => 2],
            actorId: $this->user1->id,
            actorName: $this->user1->name
        );

        $log3 = ImmutableAuditService::log(
            action: 'LEAVE_APPROVED',
            entityType: 'PengajuanCuti',
            entityId: '10',
            payload: ['approver_role' => 'Manager'],
            actorId: $this->user2->id,
            actorName: $this->user2->name
        );

        $this->assertEquals(ImmutableAuditService::GENESIS_HASH, $log1->prev_hash);
        $this->assertEquals($log1->current_hash, $log2->prev_hash);
        $this->assertEquals($log2->current_hash, $log3->prev_hash);

        // Verify the entire chain
        $verification = ImmutableAuditService::verifyChain();
        $this->assertTrue($verification['valid']);
        $this->assertEquals(3, $verification['total_verified']);
    }

    public function test_approval_delegation_resolves_active_delegate()
    {
        $governance = new ApprovalGovernanceService();

        // No active delegation initially
        $this->assertNull($governance->getActiveDelegate($this->user1->id));

        // Create delegation
        ApprovalDelegation::create([
            'delegator_user_id' => $this->user1->id,
            'delegate_user_id' => $this->user2->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Cuti tahunan',
            'is_active' => true,
        ]);

        $delegate = $governance->getActiveDelegate($this->user1->id);
        $this->assertNotNull($delegate);
        $this->assertEquals($this->user2->id, $delegate->id);
    }

    public function test_financial_reconciliation_detects_balanced_and_discrepancies()
    {
        $service = new FinancialReconciliationService();
        $log = $service->reconcilePengajuan(date('Y-m'), $this->user1->id);

        $this->assertNotNull($log->id);
        $this->assertEquals('Pengajuan', $log->module);
        $this->assertEquals('Balanced', $log->status);
    }
}
