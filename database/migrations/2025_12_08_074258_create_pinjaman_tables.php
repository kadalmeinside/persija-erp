<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tbl_pinjaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_karyawan');
            $table->date('tanggal_pengajuan');
            $table->decimal('jumlah_pinjaman', 15, 2);
            $table->integer('tenor_bulan');
            $table->decimal('bunga_persen', 5, 2)->default(0);
            $table->decimal('jumlah_angsuran_per_bulan', 15, 2);
            $table->text('keterangan')->nullable();
            $table->enum('status', ['Draft', 'Submitted', 'Pending Approval', 'Revision', 'Approved', 'Rejected', 'Paid Off'])->default('Draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('id_karyawan')->references('id')->on('tbl_karyawan')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('tbl_angsuran_pinjaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pinjaman');
            $table->string('bulan_periode', 7); // YYYY-MM
            $table->decimal('jumlah_angsuran', 15, 2);
            $table->enum('status_bayar', ['Pending', 'Paid', 'Skipped'])->default('Pending');
            $table->unsignedBigInteger('id_payroll_detail')->nullable(); // Link to payroll deduction
            $table->timestamps();

            $table->foreign('id_pinjaman')->references('id')->on('tbl_pinjaman')->onDelete('cascade');
            $table->foreign('id_payroll_detail')->references('id')->on('tbl_payroll_detail')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_angsuran_pinjaman');
        Schema::dropIfExists('tbl_pinjaman');
    }
};
