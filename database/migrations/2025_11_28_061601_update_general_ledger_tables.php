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
        Schema::table('tbl_jurnal_header', function (Blueprint $table) {
            $table->string('tipe_transaksi')->default('Manual')->after('deskripsi_jurnal');
            $table->string('status')->default('Posted')->after('tipe_transaksi');
            $table->foreignId('created_by')->nullable()->constrained('users')->after('status');
        });

        Schema::table('tbl_jurnal_detail', function (Blueprint $table) {
            $table->string('keterangan_baris')->nullable()->after('kredit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_jurnal_header', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['tipe_transaksi', 'status', 'created_by']);
        });

        Schema::table('tbl_jurnal_detail', function (Blueprint $table) {
            $table->dropColumn('keterangan_baris');
        });
    }
};
