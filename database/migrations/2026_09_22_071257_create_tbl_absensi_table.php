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
        Schema::create('tbl_absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan')->onDelete('cascade');
            $table->date('tanggal');
            $table->dateTime('waktu_masuk')->nullable();
            $table->dateTime('waktu_keluar')->nullable();
            $table->decimal('lat_masuk', 10, 8)->nullable();
            $table->decimal('lng_masuk', 11, 8)->nullable();
            $table->decimal('lat_keluar', 10, 8)->nullable();
            $table->decimal('lng_keluar', 11, 8)->nullable();
            $table->string('foto_masuk')->nullable();
            $table->string('foto_keluar')->nullable();
            $table->enum('status_kehadiran', ['Hadir', 'Terlambat', 'Lupa Checkout', 'Luar Kantor', 'Tidak Hadir'])->default('Hadir');
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['id_karyawan', 'tanggal'], 'absensi_karyawan_tanggal_unique');
        });

        Schema::create('attendance_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action', 20);
            $table->string('request_id', 36);
            $table->string('nonce_hash', 64)->unique();
            $table->json('security_metadata')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'request_id']);
            $table->index(['user_id', 'action', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_absensi');
        Schema::dropIfExists('attendance_challenges');
    }
};
