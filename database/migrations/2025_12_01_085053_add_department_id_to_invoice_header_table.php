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
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->foreignId('id_departemen')->nullable()->after('nomor_invoice')->constrained('tbl_departemen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->dropForeign(['id_departemen']);
            $table->dropColumn('id_departemen');
        });
    }
};
