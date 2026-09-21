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
            $table->unsignedBigInteger('id_verifier')->nullable()->after('selisih');
            $table->timestamp('verified_at')->nullable()->after('id_verifier');
            $table->uuid('verify_uuid')->nullable()->after('verified_at')->unique();
            
            $table->foreign('id_verifier')->references('id')->on('tbl_karyawan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->dropForeign(['id_verifier']);
            $table->dropColumn(['id_verifier', 'verified_at', 'verify_uuid']);
        });
    }
};
