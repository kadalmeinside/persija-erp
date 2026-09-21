<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom min_amount ke tbl_approval_rules.
     *
     * Jika terisi, step approval ini hanya akan diaktifkan jika nominal dokumen
     * >= min_amount. Jika nominal < min_amount, step otomatis di-skip
     * (sama seperti mekanisme self-approval auto-skip yang sudah ada).
     *
     * Contoh penggunaan:
     *   - Rule Level 2 (Direktur) dengan min_amount = 1000000
     *   - Invoice Rp 500.000 → Level 2 di-skip karena < min_amount
     *   - Invoice Rp 5.000.000 → Level 2 aktif karena >= min_amount
     */
    public function up(): void
    {
        Schema::table('tbl_approval_rules', function (Blueprint $table) {
            $table->decimal('min_amount', 15, 2)->nullable()->after('label_aksi')
                ->comment('Jika diisi, step ini hanya aktif jika nominal dokumen >= min_amount. NULL = berlaku untuk semua nominal.');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_approval_rules', function (Blueprint $table) {
            $table->dropColumn('min_amount');
        });
    }
};
