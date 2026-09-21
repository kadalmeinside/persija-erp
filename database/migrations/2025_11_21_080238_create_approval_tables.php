<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. MASTER RULE: Siapa menyetujui apa (Perorangan)
        Schema::create('tbl_approval_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_departemen')->constrained('tbl_departemen');
            $table->integer('level_order'); // 1, 2, 3...
            
            // PERUBAHAN: Menunjuk orang spesifik, bukan jabatan string
            $table->foreignId('id_karyawan_approver')->constrained('tbl_karyawan');
            
            $table->string('label_aksi'); // 'Diketahui', 'Disetujui'
            $table->timestamps();
        });

        // 2. PROSES TRANSAKSI
        Schema::create('tbl_approval_process', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengajuan')->constrained('tbl_pengajuan_header')->onDelete('cascade');
            
            $table->integer('level_order'); 
            
            // Snapshot: Siapa yang SEHARUSNYA approve (Target)
            $table->foreignId('id_karyawan_target')->constrained('tbl_karyawan');
            
            // Snapshot: Siapa yang AKTUAL melakukan approve (Realisasi - bisa sama atau beda jika ada fitur delegation)
            $table->unsignedBigInteger('id_karyawan_action')->nullable(); // User yang klik tombol
            
            $table->string('label_aksi');
            $table->enum('status', ['Waiting', 'Pending', 'Approved', 'Rejected'])->default('Waiting');
            
            $table->dateTime('tgl_aksi')->nullable();
            $table->text('catatan')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_approval_process');
        Schema::dropIfExists('tbl_approval_rules');
    }
};