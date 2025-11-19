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
        Schema::create('tbl_akun_gl', function (Blueprint $table) {
            $table->id('id_akun');
            $table->string('kode_akun', 50)->unique();
            $table->string('nama_akun', 255);
            $table->enum('tipe_akun', ['Aset', 'Utang', 'Modal', 'Pendapatan', 'Biaya', 'Biaya Modal']);
            $table->timestamps();
        });

        // tbl_Departemen (Cost Center)
        Schema::create('tbl_departemen', function (Blueprint $table) {
            $table->id('id_departemen');
            $table->string('nama_departemen', 255);
            $table->timestamps();
        });

        // tbl_Program_Kerja (Dimensi Analisis)
        Schema::create('tbl_program_kerja', function (Blueprint $table) {
            $table->id('id_program');
            $table->string('nama_program', 255);
            $table->timestamps();
        });

        // tbl_Vendor
        Schema::create('tbl_vendor', function (Blueprint $table) {
            $table->id('id_vendor');
            $table->string('nama_vendor', 255);
            $table->timestamps();
        });

        // tbl_Customer
        Schema::create('tbl_customer', function (Blueprint $table) {
            $table->id('id_customer');
            $table->string('nama_customer', 255);
            $table->timestamps();
        });

        // tbl_Pajak
        Schema::create('tbl_pajak', function (Blueprint $table) {
            $table->id('id_pajak');
            $table->string('kode_pajak', 50);
            $table->decimal('persentase', 5, 2);
            $table->foreignId('id_akun_pajak')->constrained('tbl_akun_gl');
            $table->timestamps();
        });

        // tbl_Item
        Schema::create('tbl_item', function (Blueprint $table) {
            $table->id('id_item');
            $table->string('kode_item', 100)->unique();
            $table->string('nama_item', 255);
            $table->enum('tipe_item', ['Stok', 'Non-Stok', 'Jasa', 'Aset']);
            $table->timestamps();
        });

        // tbl_Gudang
        Schema::create('tbl_gudang', function (Blueprint $table) {
            $table->id('id_gudang');
            $table->string('nama_gudang', 255);
            $table->string('lokasi', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_gudang');
        Schema::dropIfExists('tbl_item');
        Schema::dropIfExists('tbl_pajak');
        Schema::dropIfExists('tbl_customer');
        Schema::dropIfExists('tbl_vendor');
        Schema::dropIfExists('tbl_program_kerja');
        Schema::dropIfExists('tbl_departemen');
        Schema::dropIfExists('tbl_akun_gl');
    }
};
