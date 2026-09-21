<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->foreignId('id_karyawan_penerima')->nullable()->after('id_vendor_penerima')->constrained('tbl_karyawan');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->dropForeign(['id_karyawan_penerima']);
            $table->dropColumn('id_karyawan_penerima');
        });
    }
};