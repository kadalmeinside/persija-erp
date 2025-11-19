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
        // GRUP 3: MODUL ANGGARAN (Kontrol)
        
        Schema::create('tbl_budget_master', function (Blueprint $table) {
            $table->engine = 'InnoDB'; // PERBAIKAN: Paksa InnoDB
            $table->id(); 
            $table->year('tahun');
            $table->foreignId('id_departemen')->constrained('tbl_departemen');
            $table->foreignId('id_akun')->constrained('tbl_akun_gl');
            $table->foreignId('id_program')->constrained('tbl_program_kerja');

            $table->decimal('anggaran_total_tahun', 15, 2)->default(0);
            $table->decimal('anggaran_terikat_ytd', 15, 2)->default(0);
            $table->decimal('anggaran_realisasi_ytd', 15, 2)->default(0);
            $table->decimal('pacing_jan', 15, 2)->default(0);
            $table->decimal('pacing_feb', 15, 2)->default(0);
            $table->decimal('pacing_mar', 15, 2)->default(0);
            $table->decimal('pacing_apr', 15, 2)->default(0);
            $table->decimal('pacing_mei', 15, 2)->default(0);
            $table->decimal('pacing_jun', 15, 2)->default(0);
            $table->decimal('pacing_jul', 15, 2)->default(0);
            $table->decimal('pacing_agu', 15, 2)->default(0);
            $table->decimal('pacing_sep', 15, 2)->default(0);
            $table->decimal('pacing_okt', 15, 2)->default(0);
            $table->decimal('pacing_nov', 15, 2)->default(0);
            $table->decimal('pacing_des', 15, 2)->default(0);

            $table->timestamps();
            $table->unique(['tahun', 'id_departemen', 'id_akun', 'id_program'], 'budget_unique_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_budget_master');
        Schema::enableForeignKeyConstraints();
    }
};