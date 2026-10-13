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
        Schema::create('tbl_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('endpoint', 255);
            $table->string('request_hash', 64);
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->integer('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['key', 'status']);
        });

        Schema::create('tbl_reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7); // YYYY-MM
            $table->string('module', 50); // Pengajuan, Payroll, Invoice
            $table->decimal('total_expected', 15, 2);
            $table->decimal('total_actual', 15, 2);
            $table->decimal('discrepancy', 15, 2)->default(0.00);
            $table->enum('status', ['Balanced', 'Discrepancy_Found', 'Resolved'])->default('Balanced');
            $table->json('details')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period', 'module', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_reconciliation_logs');
        Schema::dropIfExists('tbl_idempotency_keys');
    }
};
