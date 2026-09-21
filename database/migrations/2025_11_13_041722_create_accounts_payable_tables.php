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
        // 1. Header Pengajuan (Parent)
        Schema::create('tbl_pengajuan_header', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            $table->string('nomor_pengajuan', 100)->unique(); // PJ-171000123
            
            $table->string('judul_pengajuan', 255);
            
            // Relasi ke User/Karyawan (Asumsi tabel users/tbl_karyawan sudah ada sebelumnya)
            // Jika belum ada, gunakan unsignedBigInteger tanpa constrained dulu
            $table->unsignedBigInteger('id_pengaju'); 
            
            $table->foreignId('id_departemen')->constrained('tbl_departemen');
            $table->date('tgl_pengajuan');
            
            $table->enum('tipe_pengajuan', ['Langsung', 'UangMuka']); 
            $table->enum('metode_pembayaran', ['Transfer', 'Cash'])->default('Transfer');
            
            // Relasi Vendor (Nullable karena Uang Muka tidak pakai Vendor)
            $table->foreignId('id_vendor_penerima')->nullable()->constrained('tbl_vendor'); 

            // --- KOLOM BARU (SNAPSHOT BANK) ---
            // Menyimpan data rekening tujuan SAAT transaksi dibuat.
            // Bisa berasal dari Master Vendor atau Input Manual Karyawan (Uang Muka).
            $table->string('bank_tujuan', 50)->nullable();
            $table->string('no_rek_tujuan', 50)->nullable();
            $table->string('atas_nama_tujuan', 100)->nullable();
            // ----------------------------------

            $table->decimal('total_nominal_diajukan', 15, 2);
            $table->enum('status_global', ['Draft', 'Pending Approval', 'Approved', 'Paid', 'Verification' , 'Rejected', 'Settled']);
            $table->text('catatan_header')->nullable();
            
            $table->string('attachment_path', 255)->nullable(); // Nullable untuk Uang Muka (opsional)

            $table->timestamps();
        });

        // 2. Detail Pengajuan (Items / Rincian Anggaran)
        Schema::create('tbl_pengajuan_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            $table->foreignId('id_pengajuan')->constrained('tbl_pengajuan_header')->onDelete('cascade');
            
            $table->string('deskripsi_item', 255);
            $table->decimal('nominal_item', 15, 2);
            
            // Relasi Anggaran (Wajib ada untuk memotong budget)
            $table->foreignId('id_program')->constrained('tbl_program_kerja');
            $table->foreignId('id_akun')->constrained('tbl_akun_gl');
            
            $table->foreignId('id_pajak')->nullable()->constrained('tbl_pajak');
            
            $table->timestamps();
        });

        // 3. Log Persetujuan (Approval History)
        Schema::create('tbl_log_persetujuan', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            $table->foreignId('id_pengajuan')->constrained('tbl_pengajuan_header')->onDelete('cascade');
            
            $table->unsignedBigInteger('id_approver'); // User ID yang melakukan approval
            $table->dateTime('tgl_aksi');
            $table->enum('status_aksi', ['Approved', 'Rejected', 'Requested Change']);
            $table->text('catatan_approver')->nullable();
            
            $table->timestamps();
        });
        
        // 4. Laporan Penggunaan (Settlement Uang Muka)
        Schema::create('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            // Relasi ke Header Pengajuan Awal (Uang Muka)
            $table->foreignId('id_pengajuan_uam')->constrained('tbl_pengajuan_header'); 
            
            $table->unsignedBigInteger('id_pelapor'); 
            $table->date('tgl_laporan');
            
            $table->decimal('total_realisasi_aktual', 15, 2);
            $table->decimal('selisih', 15, 2); // Bisa positif (sisa dikembalikan) atau negatif (reimburse)
            
            $table->timestamps();
        });

        // 5. Detail Laporan (Rincian Bon Asli)
        Schema::create('tbl_laporan_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            $table->foreignId('id_laporan')->constrained('tbl_laporan_penggunaan')->onDelete('cascade');
            
            $table->string('deskripsi_bon', 255);
            $table->decimal('nominal_bon', 15, 2);
            
            // Realisasi dibebankan ke mana?
            $table->foreignId('id_departemen_beban')->constrained('tbl_departemen');
            $table->foreignId('id_program_beban')->constrained('tbl_program_kerja');
            $table->foreignId('id_akun_beban')->constrained('tbl_akun_gl');
            
            $table->foreignId('id_pajak')->nullable()->constrained('tbl_pajak');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_laporan_detail');
        Schema::dropIfExists('tbl_laporan_penggunaan');
        Schema::dropIfExists('tbl_log_persetujuan');
        Schema::dropIfExists('tbl_pengajuan_detail');
        Schema::dropIfExists('tbl_pengajuan_header');
        Schema::enableForeignKeyConstraints();
    }
};