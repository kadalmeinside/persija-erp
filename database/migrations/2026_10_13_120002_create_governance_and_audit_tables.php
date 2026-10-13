<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Immutable Audit Log (Hash Chained / Tamper Evident)
        Schema::create('tbl_immutable_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sequence_number')->unique();
            $table->string('prev_hash', 64)->default('0000000000000000000000000000000000000000000000000000000000000000');
            $table->string('current_hash', 64);
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->string('action', 100); // e.g. APPROVAL_SUBMITTED, APPROVAL_APPROVED, REASSIGNMENT
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 150);
            $table->json('payload');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });

        // 2. Approval Delegation Table
        Schema::create('tbl_approval_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('delegate_user_id')->constrained('users')->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['delegator_user_id', 'is_active', 'start_date', 'end_date'], 'appr_delegations_active_idx');
        });

        // 3. Rule Versioning in tbl_approval_rules
        if (Schema::hasTable('tbl_approval_rules')) {
            Schema::table('tbl_approval_rules', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_approval_rules', 'version')) {
                    $table->unsignedInteger('version')->default(1);
                }
            });
        }

        // 4. Governance & SLA in tbl_approval_process
        if (Schema::hasTable('tbl_approval_process')) {
            Schema::table('tbl_approval_process', function (Blueprint $table) {
                if (!Schema::hasColumn('tbl_approval_process', 'rule_version')) {
                    $table->unsignedInteger('rule_version')->default(1);
                }
                if (!Schema::hasColumn('tbl_approval_process', 'reassigned_from_user_id')) {
                    $table->foreignId('reassigned_from_user_id')->nullable()->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('tbl_approval_process', 'reassigned_reason')) {
                    $table->string('reassigned_reason', 255)->nullable();
                }
                if (!Schema::hasColumn('tbl_approval_process', 'due_at')) {
                    $table->timestamp('due_at')->nullable();
                }
                if (!Schema::hasColumn('tbl_approval_process', 'sla_escalated_at')) {
                    $table->timestamp('sla_escalated_at')->nullable();
                }
                if (!Schema::hasColumn('tbl_approval_process', 'sla_escalation_count')) {
                    $table->unsignedInteger('sla_escalation_count')->default(0);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_approval_process')) {
            Schema::table('tbl_approval_process', function (Blueprint $table) {
                $columns = ['sla_escalation_count', 'sla_escalated_at', 'due_at', 'reassigned_reason', 'reassigned_from_user_id', 'rule_version'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('tbl_approval_process', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('tbl_approval_rules')) {
            Schema::table('tbl_approval_rules', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_approval_rules', 'version')) {
                    $table->dropColumn('version');
                }
            });
        }

        Schema::dropIfExists('tbl_approval_delegations');
        Schema::dropIfExists('tbl_immutable_audit_logs');
    }
};
