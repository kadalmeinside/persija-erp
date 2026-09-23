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
            if (!Schema::hasColumn('tbl_absensi', 'is_dinas_luar')) {
                $table->boolean('is_dinas_luar')->default(false);
            }
            if (!Schema::hasColumn('tbl_absensi', 'catatan')) {
                $table->text('catatan')->nullable();
            }
            if (!Schema::hasColumn('tbl_absensi', 'id_lokasi_kantor')) {
                $table->unsignedBigInteger('id_lokasi_kantor')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_absensi', function (Blueprint $table) {
            $table->dropColumn(['id_lokasi_kantor']);
            // is_dinas_luar & catatan may have already existed, leave them
        });
    }
};
