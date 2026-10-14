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
        Schema::create('tbl_karyawan_komponen_gaji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->onDelete('cascade');
            $table->foreignId('id_komponen_gaji')->constrained('tbl_gaji_komponen')->onDelete('restrict');
            $table->decimal('nominal', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['id_karyawan', 'id_komponen_gaji'], 'unique_karyawan_komponen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_karyawan_komponen_gaji');
    }
};
