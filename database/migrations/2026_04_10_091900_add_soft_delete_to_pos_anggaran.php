<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan is_active dan soft delete ke tbl_pos_anggaran.
     *
     * Tujuan: Pos Anggaran yang sudah tidak digunakan di tahun mendatang
     * dapat diarsipkan (is_active = false) atau di-soft-delete tanpa
     * merusak referensi historis di tbl_budget_master dan transaksi lama.
     */
    public function up(): void
    {
        Schema::table('tbl_pos_anggaran', function (Blueprint $table) {
            // Status aktif: false = diarsipkan, tidak muncul di dropdown baru
            $table->boolean('is_active')->default(true)->after('id_akun_gl');
            // Catatan arsip opsional
            $table->string('catatan_arsip', 255)->nullable()->after('is_active');
            // Soft delete — data tetap ada di DB, relasi historis tidak rusak
            $table->softDeletes()->after('catatan_arsip');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pos_anggaran', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'catatan_arsip', 'deleted_at']);
        });
    }
};
