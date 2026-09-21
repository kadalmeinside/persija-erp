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
            $table->foreignId('id_pinjaman')->nullable()->after('id_cuti')
                  ->constrained('tbl_pinjaman')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_approval_process', function (Blueprint $table) {
            $table->dropForeign(['id_pinjaman']);
            $table->dropColumn('id_pinjaman');
        });
    }
};
