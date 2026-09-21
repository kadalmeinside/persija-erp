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
        
        Schema::create('tbl_periode_anggaran', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_periode', 100); // Contoh: "Musim 2025/2026"
            $table->date('tanggal_mulai');       // Contoh: 2025-07-01
            $table->date('tanggal_selesai');     // Contoh: 2026-06-30
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // 1.1. Create tbl_pos_anggaran (Mapping Program <-> COA)
        Schema::create('tbl_pos_anggaran', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_program_kerja')->constrained('tbl_program_kerja')->onDelete('cascade');
            $table->foreignId('id_akun_gl')->constrained('tbl_akun_gl')->onDelete('cascade');
            
            $table->timestamps();
            
            // Prevent duplicate mapping
            $table->unique(['id_program_kerja', 'id_akun_gl'], 'pos_anggaran_unique');
        });

        // 1.2. Create tbl_pos_anggaran_delegasi (Delegation)
        Schema::create('tbl_pos_anggaran_delegasi', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_pos_anggaran')->constrained('tbl_pos_anggaran')->onDelete('cascade');
            $table->foreignId('id_departemen')->constrained('tbl_departemen')->onDelete('cascade'); // Dept that receives delegation
            
            $table->timestamps();

            // Prevent duplicate delegation
            $table->unique(['id_pos_anggaran', 'id_departemen'], 'delegasi_unique');
        });

        // 2. TABEL BUDGET MASTER (Header) - Dirombak
        Schema::create('tbl_budget_master', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            
            // Relasi ke Periode (Bukan lagi tahun integer)
            $table->foreignId('id_periode_anggaran')->constrained('tbl_periode_anggaran');
            // id_departemen removed (Centralized Budgeting: Owner is Program Owner)
            
            // Refactored: Use Pos Anggaran ID instead of separate Program & Account
            $table->foreignId('id_pos_anggaran')->constrained('tbl_pos_anggaran'); // Link to Program+Akun

            // Agregat Tahunan (Masih disimpan di header untuk performa)
            $table->decimal('anggaran_total_tahun', 15, 2);
            $table->decimal('anggaran_terikat_ytd', 15, 2)->default(0);
            $table->decimal('anggaran_realisasi_ytd', 15, 2)->default(0);

            // Kolom pacing horizontal (pacing_jan, dll) DIHAPUS
            
            $table->timestamps();
            $table->softDeletes();

            // Unique: Periode + Pos Anggaran (One budget per Pos per Period)
            $table->unique(['id_periode_anggaran', 'id_pos_anggaran'], 'budget_unique');
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