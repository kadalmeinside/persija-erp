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
        Schema::create('tbl_po_header', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_vendor')->constrained('tbl_vendor');
            $table->string('nomor_po', 100)->unique();
            $table->date('tgl_po');
            $table->decimal('total_po', 15, 2);
            $table->enum('status_po', ['Draft', 'Approved', 'Closed', 'Cancelled']);
            $table->timestamps();
        });

        Schema::create('tbl_po_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_po')->constrained('tbl_po_header')->onDelete('cascade');
            $table->foreignId('id_item')->constrained('tbl_item');
            $table->decimal('kuantitas', 15, 2);
            $table->decimal('harga_satuan', 15, 2);
            $table->timestamps();
        });

        Schema::create('tbl_grn_header', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_po')->nullable()->constrained('tbl_po_header');
            $table->foreignId('id_gudang')->constrained('tbl_gudang');
            $table->string('nomor_grn', 100)->unique();
            $table->date('tgl_terima');
            $table->timestamps();
        });

        Schema::create('tbl_grn_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_grn')->constrained('tbl_grn_header')->onDelete('cascade');
            $table->foreignId('id_item')->constrained('tbl_item');
            $table->decimal('kuantitas_diterima', 15, 2);
            $table->timestamps();
        });

        Schema::create('tbl_inventaris_stok', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_item')->constrained('tbl_item');
            $table->foreignId('id_gudang')->constrained('tbl_gudang');
            $table->decimal('stok_qty_on_hand', 15, 2)->default(0);
            $table->unique(['id_item', 'id_gudang']);
            $table->timestamps();
        });

        Schema::create('tbl_inventaris_keluar', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_item')->constrained('tbl_item');
            $table->foreignId('id_gudang')->constrained('tbl_gudang');
            $table->foreignId('id_departemen_pemakai')->constrained('tbl_departemen');
            $table->decimal('kuantitas_keluar', 15, 2);
            $table->date('tgl_keluar');
            $table->timestamps();
        });
        
        Schema::create('tbl_faktur_vendor', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_vendor')->constrained('tbl_vendor');
            $table->foreignId('id_po')->nullable()->constrained('tbl_po_header');
            $table->string('nomor_faktur', 100);
            $table->date('tgl_faktur');
            $table->decimal('total_faktur', 15, 2);
            $table->timestamps();
        });

        Schema::create('tbl_faktur_jual_header', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_customer')->constrained('tbl_customer');
            $table->string('nomor_faktur', 100)->unique();
            $table->date('tgl_faktur');
            $table->decimal('total_faktur', 15, 2);
            $table->enum('status_pembayaran', ['Unpaid', 'Paid', 'Partial']);
            $table->timestamps();
        });

        Schema::create('tbl_faktur_jual_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_faktur_jual')->constrained('tbl_faktur_jual_header')->onDelete('cascade');
            $table->foreignId('id_item')->constrained('tbl_item');
            $table->string('deskripsi_jasa', 255)->nullable();
            $table->decimal('kuantitas', 15, 2);
            $table->decimal('harga_satuan', 15, 2);
            $table->foreignId('id_pajak')->nullable()->constrained('tbl_pajak');
            $table->timestamps();
        });

        Schema::create('tbl_penerimaan_bayar', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_faktur_jual')->nullable()->constrained('tbl_faktur_jual_header');
            $table->foreignId('id_customer')->constrained('tbl_customer');
            $table->date('tgl_bayar');
            $table->decimal('jumlah_bayar', 15, 2);
            $table->string('metode_bayar', 100);
            $table->timestamps();
        });

        Schema::create('tbl_aset_master', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_item')->nullable()->constrained('tbl_item');
            $table->string('nama_aset', 255);
            $table->foreignId('id_departemen_lokasi')->constrained('tbl_departemen');
            $table->date('tgl_perolehan');
            $table->decimal('nilai_perolehan', 15, 2);
            $table->decimal('nilai_sisa', 15, 2)->default(0);
            $table->integer('masa_manfaat_bulan');
            $table->enum('metode_depresiasi', ['Straight Line']);
            
            $table->foreignId('id_akun_aset')->constrained('tbl_akun_gl');
            $table->foreignId('id_akun_akumulasi')->constrained('tbl_akun_gl');
            $table->foreignId('id_akun_beban')->constrained('tbl_akun_gl');
            
            $table->enum('status_aset', ['Active', 'Disposed']);
            $table->timestamps();
        });
        
        Schema::create('tbl_aset_depresiasi_log', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_aset')->constrained('tbl_aset_master');
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->decimal('nilai_depresiasi_bulan', 15, 2);
            $table->foreignId('id_jurnal')->nullable()->constrained('tbl_jurnal_header');
            $table->timestamps();
        });

        Schema::create('tbl_karyawan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->foreignId('id_departemen')->constrained('tbl_departemen');
            $table->string('nomor_induk_karyawan', 100)->unique();
            $table->string('nama_lengkap', 255);
            $table->string('jabatan', 255);
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->string('status_ptkp', 50)->nullable(); 
            $table->string('info_bank', 255)->nullable();
            $table->timestamps();
        });
        
        Schema::create('tbl_payroll_header', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->enum('status_payroll', ['Draft', 'Calculated', 'Paid']);
            $table->timestamps();
        });

        Schema::create('tbl_payroll_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_payroll')->constrained('tbl_payroll_header')->onDelete('cascade');
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan');
            
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('potongan_pph21', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2);
            
            $table->foreignId('id_jurnal')->nullable()->constrained('tbl_jurnal_header');
            $table->timestamps();
        });

        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->foreign('id_pengaju')->references('id')->on('tbl_karyawan');
        });
        
        Schema::table('tbl_log_persetujuan', function (Blueprint $table) {
            $table->foreign('id_approver')->references('id')->on('tbl_karyawan');
        });

        Schema::table('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->foreign('id_pelapor')->references('id')->on('tbl_karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('tbl_pengajuan_header', function (Blueprint $table) {
            $table->dropForeign(['id_pengaju']);
        });
        Schema::table('tbl_log_persetujuan', function (Blueprint $table) {
            $table->dropForeign(['id_approver']);
        });
        Schema::table('tbl_laporan_penggunaan', function (Blueprint $table) {
            $table->dropForeign(['id_pelapor']);
        });

        Schema::dropIfExists('tbl_payroll_detail');
        Schema::dropIfExists('tbl_payroll_header');
        Schema::dropIfExists('tbl_karyawan');
        Schema::dropIfExists('tbl_aset_depresiasi_log');
        Schema::dropIfExists('tbl_aset_master');
        Schema::dropIfExists('tbl_penerimaan_bayar');
        Schema::dropIfExists('tbl_faktur_jual_detail');
        Schema::dropIfExists('tbl_faktur_jual_header');
        Schema::dropIfExists('tbl_faktur_vendor');
        Schema::dropIfExists('tbl_inventaris_keluar');
        Schema::dropIfExists('tbl_inventaris_stok');
        Schema::dropIfExists('tbl_grn_detail');
        Schema::dropIfExists('tbl_grn_header');
        Schema::dropIfExists('tbl_po_detail');
        Schema::dropIfExists('tbl_po_header');
        
        Schema::enableForeignKeyConstraints();
    }
};