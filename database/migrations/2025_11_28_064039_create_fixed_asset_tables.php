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
        // 1. Tabel Aset Tetap
        Schema::create('tbl_aset', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_aset', 50)->unique();
            $table->string('nama_aset', 255);
            $table->string('kategori', 100); // Elektronik, Furniture, Kendaraan, Bangunan
            $table->date('tgl_perolehan');
            $table->decimal('harga_perolehan', 15, 2);
            $table->decimal('nilai_sisa', 15, 2)->default(0); // Salvage Value
            $table->integer('umur_manfaat_bulan'); // Useful Life in Months
            $table->string('metode_penyusutan', 50)->default('Straight Line');
            
            // Akun GL Mapping
            $table->foreignId('id_akun_aset')->constrained('tbl_akun_gl'); // Fixed Asset Account
            $table->foreignId('id_akun_akumulasi_penyusutan')->constrained('tbl_akun_gl'); // Accumulated Depreciation Account
            $table->foreignId('id_akun_beban_penyusutan')->constrained('tbl_akun_gl'); // Depreciation Expense Account
            
            $table->string('status', 50)->default('Active'); // Active, Disposed, Fully Depreciated
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Riwayat Penyusutan
        Schema::create('tbl_penyusutan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_aset')->constrained('tbl_aset')->onDelete('cascade');
            $table->date('tgl_penyusutan'); // End of month usually
            $table->decimal('nilai_buku_awal', 15, 2);
            $table->decimal('nilai_penyusutan', 15, 2);
            $table->decimal('nilai_buku_akhir', 15, 2);
            $table->boolean('is_posted')->default(false);
            $table->string('jurnal_ref', 100)->nullable(); // Reference to GL Journal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_penyusutan');
        Schema::dropIfExists('tbl_aset');
        Schema::enableForeignKeyConstraints();
    }
};
