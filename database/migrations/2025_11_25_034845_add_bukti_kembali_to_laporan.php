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
        Schema::table('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->string('bukti_pengembalian_path')->nullable()->after('selisih');
        });
        Schema::table('tbl_laporan_detail', function (Blueprint $table) {
            $table->string('bukti_path')->nullable()->after('nominal_bon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->dropColumn('bukti_pengembalian_path');
        });
         Schema::table('tbl_laporan_detail', function (Blueprint $table) {
            $table->dropColumn('bukti_path');
        });
    }
};
