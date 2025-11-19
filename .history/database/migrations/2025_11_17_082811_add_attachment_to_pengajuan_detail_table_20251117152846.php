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
        Schema::table('tbl_pengajuan_detail', function (Blueprint $table) {
            $table->string('attachment_path', 255)->nullable()->after('id_vendor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_pengajuan_detail', function (Blueprint $table) {
            $table->dropColumn('attachment_path');
        });
    }
};
