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
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pengajuan')->nullable()->change(); // Make pengajuan nullable
            $table->unsignedBigInteger('id_cuti')->nullable()->after('id_pengajuan');
            
            // Optional: Add foreign key if needed, or just index
            // $table->foreign('id_cuti')->references('id')->on('tbl_pengajuan_cuti')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->dropColumn('id_cuti');
            $table->unsignedBigInteger('id_pengajuan')->nullable(false)->change(); // Revert
        });
    }
};
