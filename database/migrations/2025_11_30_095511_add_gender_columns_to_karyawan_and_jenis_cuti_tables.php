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
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('nama_lengkap');
        });

        Schema::table('tbl_jenis_cuti', function (Blueprint $table) {
            $table->boolean('khusus_perempuan')->default(false)->after('bisa_mundur');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->dropColumn('jenis_kelamin');
        });

        Schema::table('tbl_jenis_cuti', function (Blueprint $table) {
            $table->dropColumn('khusus_perempuan');
        });
    }
};
