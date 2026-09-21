<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom approval tracking ke tbl_invoice_header.
     *
     * Invoice sekarang menggunakan status 'Draft' (yang sudah ada di enum)
     * sebagai status awal setelah dibuat, dan hanya berpindah ke 'Unpaid'
     * setelah mendapat persetujuan final (ApprovalService).
     */
    public function up(): void
    {
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by')
                ->comment('User ID yang memberikan persetujuan final');
            $table->timestamp('approved_at')->nullable()->after('approved_by')
                ->comment('Waktu persetujuan final diberikan');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at']);
        });
    }
};
