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
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_lokasi_kantor')->nullable();
            $table->boolean('is_strict_location')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->dropColumn(['id_lokasi_kantor', 'is_strict_location']);
        });
    }
};
