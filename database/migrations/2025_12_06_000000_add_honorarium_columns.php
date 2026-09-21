<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update tbl_karyawan status_karyawan enum (MySQL/MariaDB only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tbl_karyawan MODIFY COLUMN status_karyawan ENUM('Tetap', 'Kontrak', 'Magang', 'Freelance') NOT NULL DEFAULT 'Kontrak'");
        }

        // 2. Update tbl_payroll
        Schema::table('tbl_payroll', function (Blueprint $table) {
            $table->enum('tipe_payroll', ['Bulanan', 'Honorarium'])->default('Bulanan')->after('status');
            $table->unsignedBigInteger('id_program')->nullable()->after('tipe_payroll');
            $table->foreign('id_program')->references('id')->on('tbl_program_kerja')->onDelete('set null');
        });

        // 3. Update tbl_payroll_detail
        Schema::table('tbl_payroll_detail', function (Blueprint $table) {
            $table->decimal('honorarium', 15, 2)->default(0)->after('total_tunjangan');
            $table->integer('jumlah_sesi')->default(0)->after('honorarium');
            $table->decimal('rate_per_sesi', 15, 2)->default(0)->after('jumlah_sesi');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_payroll_detail', function (Blueprint $table) {
            $table->dropColumn(['honorarium', 'jumlah_sesi', 'rate_per_sesi']);
        });

        Schema::table('tbl_payroll', function (Blueprint $table) {
            $table->dropForeign(['id_program']);
            $table->dropColumn(['tipe_payroll', 'id_program']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tbl_karyawan MODIFY COLUMN status_karyawan ENUM('Tetap', 'Kontrak', 'Magang') NOT NULL DEFAULT 'Kontrak'");
        }
    }
};
