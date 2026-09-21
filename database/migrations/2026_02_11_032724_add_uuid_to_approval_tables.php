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
        // Add uuid to tbl_approval_process
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->string('uuid')->after('id')->nullable()->unique();
        });

        // Add approval_uuid to tbl_pinjaman
        Schema::table('tbl_pinjaman', function (Blueprint $table) {
            $table->string('approval_uuid')->after('id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('tbl_pinjaman', function (Blueprint $table) {
            $table->dropColumn('approval_uuid');
        });
    }
};
