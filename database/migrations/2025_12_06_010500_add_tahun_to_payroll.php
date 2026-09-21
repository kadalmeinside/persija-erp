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
            $table->year('tahun')->nullable()->after('bulan_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_payroll', function (Blueprint $table) {
            $table->dropColumn('tahun');
        });
    }
};
