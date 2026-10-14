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
        Schema::create('tbl_karyawan_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->cascadeOnDelete();
            $table->unsignedInteger('version_number')->default(1);
            $table->string('nomor_kontrak')->nullable();
            $table->string('contract_type', 50)->default('PKWT');
            $table->string('judul_surat')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->unsignedInteger('durasi_probation_bulan')->default(0);
            $table->unsignedInteger('notice_period_hari')->default(30);
            $table->unsignedInteger('jam_kerja_mingguan')->default(40);
            $table->decimal('gaji_pokok', 15, 2)->nullable();
            $table->json('working_scope')->nullable();
            $table->longText('contract_content')->nullable(); // WYSIWYG content
            $table->longText('rendered_content')->nullable(); // Parsed snapshot
            $table->string('file_path')->nullable(); // Archived PDF path
            $table->string('status', 30)->default('active'); // active, renewed, superseded, draft, terminated
            $table->string('catatan_perubahan')->nullable(); // e.g. "Kontrak Awal PKWT", "Perpanjangan Masa Kerja", "Koreksi Klausul"
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['id_karyawan', 'status']);
            $table->index(['id_karyawan', 'version_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_karyawan_contracts');
    }
};
