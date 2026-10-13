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
        // 1. Koreksi Absensi (Attendance Correction)
        Schema::create('tbl_koreksi_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->onDelete('cascade');
            $table->foreignId('id_absensi')->nullable()->constrained('tbl_absensi')->nullOnDelete();
            $table->date('tanggal');
            $table->time('waktu_masuk_baru')->nullable();
            $table->time('waktu_keluar_baru')->nullable();
            $table->text('alasan');
            $table->string('bukti_lampiran', 255)->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_approver')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['id_karyawan', 'tanggal', 'status']);
        });

        // 2. Dinas Luar (Business Trip / Official Assignment)
        Schema::create('tbl_dinas_luar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->onDelete('cascade');
            $table->string('nomor_surat_tugas', 100)->nullable();
            $table->string('nama_kegiatan', 200);
            $table->string('lokasi_tujuan', 200);
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->text('keterangan')->nullable();
            $table->string('surat_tugas_lampiran', 255)->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_approver')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['id_karyawan', 'status', 'tgl_mulai']);
        });

        // 3. Reimbursement (Expense Claims)
        Schema::create('tbl_reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->onDelete('cascade');
            $table->string('nomor_reimbursement', 80)->unique();
            $table->string('judul_klaim', 200);
            $table->string('kategori', 80); // Transport, Konsumsi, Medis, Operasional, Lainnya
            $table->date('tgl_transaksi');
            $table->decimal('total_biaya', 15, 2);
            $table->text('keterangan')->nullable();
            $table->string('bukti_struk', 255)->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Paid'])->default('Pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_approver')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['id_karyawan', 'status', 'tgl_transaksi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_reimbursements');
        Schema::dropIfExists('tbl_dinas_luar');
        Schema::dropIfExists('tbl_koreksi_absensi');
    }
};
