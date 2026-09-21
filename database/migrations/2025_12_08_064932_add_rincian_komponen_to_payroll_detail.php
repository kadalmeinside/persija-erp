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
        if (!Schema::hasColumn('tbl_payroll_detail', 'rincian_komponen')) {
            Schema::table('tbl_payroll_detail', function (Blueprint $table) {
                // JSON column to store breakdown of payments (e.g., Salary, Honor A, Honor B)
                // Structure: [{ "type": "Gaji Pokok", "amount": 5000000, "id_program": null, "id_komponen": null }, ...]
                $table->json('rincian_komponen')->nullable()->after('gaji_bersih');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_payroll_detail', function (Blueprint $table) {
            $table->dropColumn('rincian_komponen');
        });
    }
};
