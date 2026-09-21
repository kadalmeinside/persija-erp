<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom id_kas_kecil ke tbl_pengajuan_header.
     *
     * Kolom ini hanya terisi untuk pengajuan tipe 'PettyCash'.
     * Menunjuk ke KasBank yang digunakan sebagai sumber dana petty cash.
     * GL posting saat final approve akan mengkredit akun GL dari KasBank ini.
     */
    public function up(): void
    {
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kas_kecil')->nullable()->after('atas_nama_tujuan')
                ->comment('FK ke tbl_kas_bank — hanya untuk tipe PettyCash');

            $table->foreign('id_kas_kecil')->references('id')->on('tbl_kas_bank')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->dropForeign(['id_kas_kecil']);
            $table->dropColumn('id_kas_kecil');
        });
    }
};
