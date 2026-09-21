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
        // 1. Update tbl_karyawan
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->date('tgl_bergabung')->nullable()->after('jabatan');
            $table->string('tempat_lahir', 100)->nullable()->after('tgl_bergabung');
            $table->date('tgl_lahir')->nullable()->after('tempat_lahir');
            $table->text('alamat')->nullable()->after('tgl_lahir');
            $table->enum('status_karyawan', ['Tetap', 'Kontrak', 'Magang'])->default('Tetap')->after('alamat');
        });

        // 2. Jenis Cuti (Master)
        Schema::create('tbl_jenis_cuti', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_cuti', 100); // Cuti Tahunan, Cuti Sakit, dll
            $table->integer('kuota_default')->default(12);
            $table->timestamps();
        });

        // 3. Saldo Cuti (Per Karyawan Per Tahun)
        Schema::create('tbl_saldo_cuti', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan');
            $table->foreignId('id_jenis_cuti')->constrained('tbl_jenis_cuti');
            $table->integer('tahun_periode'); // 2024
            $table->integer('saldo_awal');
            $table->integer('saldo_terpakai')->default(0);
            $table->integer('saldo_akhir'); // Computed or stored
            $table->timestamps();
            
            $table->unique(['id_karyawan', 'id_jenis_cuti', 'tahun_periode'], 'unique_saldo_per_periode');
        });

        // 4. Pengajuan Cuti
        Schema::create('tbl_pengajuan_cuti', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan'); // Yang mengajukan
            $table->foreignId('id_jenis_cuti')->constrained('tbl_jenis_cuti');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->integer('jumlah_hari');
            $table->text('alasan');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('id_approver')->nullable()->constrained('users'); // User yang menyetujui
            $table->text('catatan_approval')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_pengajuan_cuti');
        Schema::dropIfExists('tbl_saldo_cuti');
        Schema::dropIfExists('tbl_jenis_cuti');
        
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->dropColumn(['tgl_bergabung', 'tempat_lahir', 'tgl_lahir', 'alamat', 'status_karyawan']);
        });
        Schema::enableForeignKeyConstraints();
    }
};
