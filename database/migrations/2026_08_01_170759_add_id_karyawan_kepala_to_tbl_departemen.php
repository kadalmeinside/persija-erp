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
        Schema::table('tbl_departemen', function (Blueprint $table) {
            $table->unsignedBigInteger('id_karyawan_kepala')->nullable()->after('nama_departemen');
            
            // Note: we don't strictly enforce foreign key here to avoid circular dependency issues if dropping tables,
            // but for completeness we can add it.
            $table->foreign('id_karyawan_kepala')->references('id')->on('tbl_karyawan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_departemen', function (Blueprint $table) {
            $table->dropForeign(['id_karyawan_kepala']);
            $table->dropColumn('id_karyawan_kepala');
        });
    }
};
