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
        Schema::table('tbl_absensi', function (Blueprint $table) {
            $table->unique(['id_karyawan', 'tanggal'], 'absensi_karyawan_tanggal_unique');
            $table->foreign('id_lokasi_kantor')->references('id')->on('tbl_lokasi_kantor')->onDelete('set null');
        });

        Schema::table('tbl_budget_detail', function (Blueprint $table) {
            $table->unique(['id_budget_master', 'bulan', 'tahun'], 'budget_detail_unique');
        });

        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->foreign('id_cuti')->references('id')->on('tbl_pengajuan_cuti')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_absensi', function (Blueprint $table) {
            $table->dropForeign(['id_lokasi_kantor']);
            $table->dropUnique('absensi_karyawan_tanggal_unique');
        });

        Schema::table('tbl_budget_detail', function (Blueprint $table) {
            $table->dropUnique('budget_detail_unique');
        });

        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->dropForeign(['id_cuti']);
        });
    }
};
