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
        Schema::table('tbl_payroll_detail', function (Blueprint $table) {
            $table->foreignId('id_program')->nullable()->after('gaji_bersih')->constrained('tbl_program_kerja');
            $table->foreignId('id_komponen_gaji')->nullable()->after('id_program')->constrained('tbl_gaji_komponen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_payroll_detail', function (Blueprint $table) {
            $table->dropForeign(['id_program']);
            $table->dropForeign(['id_komponen_gaji']);
            $table->dropColumn(['id_program', 'id_komponen_gaji']);
        });
    }
};
