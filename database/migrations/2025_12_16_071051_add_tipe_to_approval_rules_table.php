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
        Schema::table('tbl_approval_rules', function (Blueprint $table) {
            $table->enum('tipe', ['Pengajuan', 'Cuti', 'Pinjaman'])->default('Pengajuan')->after('id_departemen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_approval_rules', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });
    }
};
