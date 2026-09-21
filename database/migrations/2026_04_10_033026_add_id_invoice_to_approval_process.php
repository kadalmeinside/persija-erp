<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom id_invoice ke tbl_approval_process.
     *
     * Dengan ini, ApprovalProcess bisa mereferensikan InvoiceHeader
     * di samping PengajuanHeader, PengajuanCuti, dan Pinjaman.
     * Hanya satu kolom FK yang akan terisi per record.
     */
    public function up(): void
    {
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->unsignedBigInteger('id_invoice')->nullable()->after('id_pinjaman')
                ->comment('FK ke tbl_invoice_header — diisi jika approval untuk Invoice');

            $table->foreign('id_invoice')->references('id')->on('tbl_invoice_header')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->dropForeign(['id_invoice']);
            $table->dropColumn('id_invoice');
        });
    }
};
