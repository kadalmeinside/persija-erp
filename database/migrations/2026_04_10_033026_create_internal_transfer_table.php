<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel Internal Transfer untuk pergerakan kas antar rekening internal.
     *
     * Use case:
     * - Replenishment petty cash (dari Rekening Utama → Kas Kecil)
     * - Optimasi likuiditas (dari Bank A → Bank B)
     * - Persiapan payroll (dari Rekening Operasional → Rekening Payroll)
     *
     * GL Posting (saat approved):
     *   Dr. KasBank Tujuan   Rp X
     *       Cr. KasBank Asal Rp X
     */
    public function up(): void
    {
        Schema::create('tbl_internal_transfer', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transfer', 50)->unique()
                ->comment('Format: IT/YYYY/MM/NNNNN');
            $table->date('tgl_transfer');
            $table->unsignedBigInteger('from_kas_bank_id')
                ->comment('Rekening/Kas sumber');
            $table->unsignedBigInteger('to_kas_bank_id')
                ->comment('Rekening/Kas tujuan');
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan')->nullable();

            // Status lifecycle: Draft → Approved | Cancelled
            $table->enum('status', ['Draft', 'Approved', 'Cancelled'])->default('Draft');

            // Audit columns
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // GL reference (terisi saat approved)
            $table->unsignedBigInteger('id_jurnal')->nullable()
                ->comment('FK ke tbl_jurnal_header — terisi setelah approved');

            $table->timestamps();

            // Foreign keys
            $table->foreign('from_kas_bank_id')->references('id')->on('tbl_kas_bank');
            $table->foreign('to_kas_bank_id')->references('id')->on('tbl_kas_bank');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('id_jurnal')->references('id')->on('tbl_jurnal_header')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_internal_transfer');
    }
};
