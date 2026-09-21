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
        // 1. Tabel Akun GL
        Schema::create('tbl_akun_gl', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_akun', 50)->unique();
            $table->string('nama_akun', 255);
            $table->enum('tipe_akun', ['Aset', 'Utang', 'Modal', 'Pendapatan', 'Biaya', 'Biaya Modal']);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Tabel Departemen
        Schema::create('tbl_departemen', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_departemen', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Tabel Program Kerja
        Schema::create('tbl_program_kerja', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_program', 255);
            $table->foreignId('id_departemen')->nullable()->constrained('tbl_departemen')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Tabel Vendor (Updated: Info Bank Dipisah)
        Schema::create('tbl_vendor', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_vendor', 50)->nullable()->unique(); 
            $table->string('nama_vendor', 255);
            
            // Kontak & Alamat
            $table->text('alamat_vendor')->nullable();
            $table->string('telepon_vendor', 50)->nullable();
            $table->string('email_vendor', 100)->nullable();
            $table->string('npwp', 50)->nullable();
            
            // NOTE: Kolom bank dihapus dari sini, pindah ke tbl_rekening_bank

            $table->string('kategori_vendor', 50)->default('Umum');
            $table->boolean('is_active')->default(true); 

            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Tabel Rekening Bank (Polymorphic: Untuk Vendor & Karyawan)
        Schema::create('tbl_rekening_bank', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            
            // Info Bank
            $table->string('nama_bank', 100);       // BCA, Mandiri, dll
            $table->string('nomor_rekening', 50);
            $table->string('atas_nama_rekening', 255);
            $table->string('cabang', 100)->nullable();

            // Polymorphic Relation (owner_id & owner_type)
            // owner_type akan berisi string model: 'App\Models\Vendor' atau 'App\Models\Karyawan'
            // owner_id akan berisi ID dari vendor atau karyawan tersebut
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_type');
            
            // Flag untuk rekening utama (jika punya banyak)
            $table->boolean('is_primary')->default(false); 

            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa pencarian
            $table->index(['owner_id', 'owner_type']);
        });

        // 6. Tabel Customer
        Schema::create('tbl_customer', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_customer', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. Tabel Item
        Schema::create('tbl_item', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_item', 100)->unique();
            $table->string('nama_item', 255);
            $table->enum('tipe_item', ['Stok', 'Non-Stok', 'Jasa', 'Aset']);
            $table->timestamps();
            $table->softDeletes();
        });

        // 8. Tabel Gudang
        Schema::create('tbl_gudang', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_gudang', 255);
            $table->string('lokasi', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 9. Tabel Pajak
        Schema::create('tbl_pajak', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_pajak', 50);
            $table->decimal('persentase', 5, 2);
            $table->foreignId('id_akun_pajak')->constrained('tbl_akun_gl');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tbl_kas_bank', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_bank', 100);      // Misal: Bank BCA
            $table->string('nomor_rekening', 50)->nullable(); // Null jika Kas Tunai
            $table->string('atas_nama', 100)->nullable();
            
            // Link ke COA (GL) untuk penjurnalan otomatis
            // Jadi saat Bank ini dipilih, sistem tahu harus kredit ke akun GL mana
            $table->foreignId('id_akun_gl')->constrained('tbl_akun_gl');
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_kas_bank');
        Schema::dropIfExists('tbl_pajak');
        Schema::dropIfExists('tbl_gudang');
        Schema::dropIfExists('tbl_item');
        Schema::dropIfExists('tbl_customer');
        Schema::dropIfExists('tbl_rekening_bank'); // Drop tabel bank
        Schema::dropIfExists('tbl_vendor');
        Schema::dropIfExists('tbl_program_kerja');
        Schema::dropIfExists('tbl_departemen');
        Schema::dropIfExists('tbl_akun_gl');
        Schema::enableForeignKeyConstraints();
    }
};