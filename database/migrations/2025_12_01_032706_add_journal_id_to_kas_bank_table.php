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
        Schema::table('tbl_kas_bank', function (Blueprint $table) {
            $table->foreignId('id_jurnal_saldo_awal')->nullable()->after('saldo_awal')->constrained('tbl_jurnal_header')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_kas_bank', function (Blueprint $table) {
            $table->dropForeign(['id_jurnal_saldo_awal']);
            $table->dropColumn('id_jurnal_saldo_awal');
        });
    }
};
