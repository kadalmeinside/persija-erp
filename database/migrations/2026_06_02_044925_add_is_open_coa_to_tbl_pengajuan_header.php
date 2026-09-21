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
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->boolean('is_open_coa')->default(false)->after('status_global')->comment('True jika finance membuka akses semua COA saat settlement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->dropColumn('is_open_coa');
        });
    }
};
