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
        Schema::create('tbl_pengajuan_header', function (Blueprint $table) {
            $table->id('id_pengajuan');
            $table->string('nomor_pengajuan', 100)->unique();
            
            // Akan menjadi FK ke tbl_karyawan (didefinisikan di migrasi 0005)
            $table->unsignedBigInteger('id_pengaju'); 

            $table->foreignId('id_departemen')->constrained('tbl_departemen');
            $table->date('tgl_pengajuan');
            $table->enum('tipe_pengajuan', ['Langsung', 'UangMuka', 'PO/Faktur']);
            $table->decimal('total_nominal_diajukan', 15, 2);
            $table->enum('status_global', ['Draft', 'Pending Approval', 'Approved', 'Paid', 'Rejected', 'Settled']);
            $table->text('catatan_header')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_log_persetujuan', function (Blueprint $table) {
            $table->id('id_log');
            $table->foreignId('id_pengajuan')->constrained('tbl_pengajuan_header')->onDelete('cascade');
            
            // Akan menjadi FK ke tbl_karyawan (didefinisikan di migrasi 0005)
            $table->unsignedBigInteger('id_approver'); 

            $table->dateTime('tgl_aksi');
            $table->enum('status_aksi', ['Approved', 'Rejected', 'Requested Change']);
            $table->text('catatan_approver')->nullable();
            $table->timestamps();
        });
        
        // --- Settlement Uang Muka ---
        Schema::create('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->id('id_laporan');
            $table->foreignId('id_pengajuan_uam')->constrained('tbl_pengajuan_header'); // Relasi ke Pengajuan Uang Muka
            
            // Akan menjadi FK ke tbl_karyawan (didefinisikan di migrasi 0005)
            $table->unsignedBigInteger('id_pelapor'); 

            $table->date('tgl_laporan');
            $table->decimal('total_realisasi_aktual', 15, 2);
            $table->decimal('selih', 15, 2); // (Total Realisasi - Nominal Uang Muka Awal)
            $table->timestamps();
        });

        Schema::create('tbl_laporan_detail', function (Blueprint $table) {
            $table->id('id_laporan_detail');
            $table->foreignId('id_laporan')->constrained('tbl_laporan_penggunaan')->onDelete('cascade');
            $table->string('deskripsi_bon', 255);
            $table->decimal('nominal_bon', 15, 2);
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
        Schema::dropIfExists('tbl_laporan_detail');
        Schema::dropIfExists('tbl_laporan_penggunaan');
        Schema::dropIfExists('tbl_log_persetujuan');
        Schema::dropIfExists('tbl_pengajuan_detail');
        Schema::dropIfExists('tbl_pengajuan_header');
    }
};
