<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    Schema::create('tbl_periode_anggaran', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_periode', 100); // Contoh: "Musim 2025/2026"
            $table->date('tanggal_mulai');       // Contoh: 2025-07-01
            $table->date('tanggal_selesai');     // Contoh: 2026-06-30
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 2. TABEL BUDGET MASTER (Header) - Dirombak
        Schema::create('tbl_budget_master', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            
            // Relasi ke Periode (Bukan lagi tahun integer)
            $table->foreignId('id_periode_anggaran')->constrained('tbl_periode_anggaran');
            
            $table->foreignId('id_departemen')->constrained('tbl_departemen');
            $table->foreignId('id_akun')->constrained('tbl_akun_gl');
            $table->foreignId('id_program')->constrained('tbl_program_kerja');

            // Agregat Tahunan (Masih disimpan di header untuk performa)
            $table->decimal('anggaran_total_tahun', 15, 2)->default(0);
            $table->decimal('anggaran_terikat_ytd', 15, 2)->default(0);
            $table->decimal('anggaran_realisasi_ytd', 15, 2)->default(0);

            // Kolom pacing horizontal (pacing_jan, dll) DIHAPUS
            
            $table->timestamps();
            // Unik per periode, dept, akun, program
            $table->unique(['id_periode_anggaran', 'id_departemen', 'id_akun', 'id_program'], 'budget_unique');
        });

        // 3. TABEL BUDGET DETAIL (Pacing Vertikal) - Baru
        Schema::create('tbl_budget_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_budget_master')->constrained('tbl_budget_master')->onDelete('cascade');
            
            $table->integer('bulan'); // 1-12
            $table->integer('tahun'); // Tahun Kalender (misal 2025 atau 2026)
            $table->decimal('nominal_pacing', 15, 2)->default(0);
            
            $table->timestamps();
            
            // Index untuk pencarian cepat
            $table->index(['id_budget_master', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_budget_detail');
        Schema::dropIfExists('tbl_budget_master');
        Schema::dropIfExists('tbl_periode_anggaran');
        Schema::enableForeignKeyConstraints();
    }
};