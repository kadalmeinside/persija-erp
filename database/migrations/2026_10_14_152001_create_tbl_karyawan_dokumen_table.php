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
        Schema::create('tbl_karyawan_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->onDelete('cascade');
            $table->string('kategori_dokumen', 50)->default('kontrak'); // kontrak, sk, ktp, npwp, bpjs, ijazah_sertifikat, lainnya
            $table->string('judul_dokumen');
            $table->string('nomor_dokumen', 100)->nullable();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->string('file_extension', 20)->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_kadaluarsa')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_karyawan', 'kategori_dokumen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_karyawan_dokumen');
    }
};
