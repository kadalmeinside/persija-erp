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
        Schema::table('tbl_payroll', function (Blueprint $table) {
            $table->foreignId('id_user_pembuat')->nullable()->after('total_gaji_bersih')->constrained('users');
            $table->dateTime('tgl_disetujui')->nullable()->after('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_payroll', function (Blueprint $table) {
            $table->dropForeign(['id_user_pembuat']);
            $table->dropColumn(['id_user_pembuat', 'tgl_disetujui']);
        });
    }
};
