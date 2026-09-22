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
        Schema::create('tbl_riwayat_karir', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_karyawan');
            $table->enum('tipe_peristiwa', [
                'Pengangkatan Awal',
                'Perpanjangan Kontrak',
                'Pengangkatan Tetap',
                'Promosi',
                'Demosi',
                'Mutasi',
                'Penyesuaian Gaji',
                'Lainnya'
            ]);
            $table->date('tanggal_efektif');
            $table->date('tanggal_berakhir_kontrak')->nullable();
            
            $table->unsignedBigInteger('id_departemen')->nullable();
            $table->string('jabatan', 100);
            $table->enum('status_karyawan', ['Tetap', 'Kontrak', 'Magang', 'Probation']);
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            
            $table->text('catatan')->nullable();
            $table->string('file_sk')->nullable();
            $table->timestamps();

            $table->foreign('id_karyawan')->references('id')->on('tbl_karyawan')->onDelete('cascade');
            $table->foreign('id_departemen')->references('id')->on('tbl_departemen')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_riwayat_karir');
    }
};
