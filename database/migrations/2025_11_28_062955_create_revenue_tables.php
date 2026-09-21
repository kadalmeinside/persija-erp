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
        // 1. Tabel Pelanggan
        Schema::create('tbl_pelanggan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_pelanggan', 50)->unique();
            $table->string('nama_pelanggan', 255);
            $table->string('email', 100)->nullable();
            $table->string('telepon', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->string('npwp', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Invoice Header
        Schema::create('tbl_invoice_header', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nomor_invoice', 100)->unique();
            $table->foreignId('id_pelanggan')->constrained('tbl_pelanggan');
            $table->date('tgl_invoice');
            $table->date('tgl_jatuh_tempo');
            $table->string('status', 50)->default('Unpaid'); // Unpaid, Partial, Paid, Cancelled
            $table->decimal('total_tagihan', 15, 2);
            $table->decimal('sisa_tagihan', 15, 2);
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 3. Tabel Invoice Detail
        Schema::create('tbl_invoice_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_invoice')->constrained('tbl_invoice_header')->onDelete('cascade');
            $table->string('deskripsi_item', 255);
            $table->integer('kuantitas');
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('total_harga', 15, 2);
            $table->foreignId('id_akun_pendapatan')->constrained('tbl_akun_gl'); // Akun Pendapatan
            $table->timestamps();
        });

        // 4. Tabel Penerimaan Pembayaran
        Schema::create('tbl_penerimaan_pembayaran', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_invoice')->constrained('tbl_invoice_header');
            $table->date('tgl_bayar');
            $table->decimal('nominal_bayar', 15, 2);
            $table->foreignId('id_kas_bank')->constrained('tbl_kas_bank'); // Bank Tujuan
            $table->string('bukti_bayar_path', 255)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_penerimaan_pembayaran');
        Schema::dropIfExists('tbl_invoice_detail');
        Schema::dropIfExists('tbl_invoice_header');
        Schema::dropIfExists('tbl_pelanggan');
        Schema::enableForeignKeyConstraints();
    }
};
