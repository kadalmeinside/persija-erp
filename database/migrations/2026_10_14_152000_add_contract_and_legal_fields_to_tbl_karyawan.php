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
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->string('nomor_kontrak', 100)->nullable()->after('nomor_induk_karyawan');
            $table->string('contract_type', 50)->default('PKWT')->after('status_karyawan');
            $table->integer('durasi_probation_bulan')->default(3)->after('contract_type');
            $table->date('tanggal_selesai_probation')->nullable()->after('durasi_probation_bulan');
            $table->integer('notice_period_hari')->default(30)->after('tanggal_selesai_probation');
            $table->integer('jam_kerja_mingguan')->default(40)->after('notice_period_hari');
            $table->string('no_bpjs_ketenagakerjaan', 50)->nullable()->after('status_ptkp');
            $table->string('no_bpjs_kesehatan', 50)->nullable()->after('no_bpjs_ketenagakerjaan');
            $table->json('working_scope')->nullable()->after('no_bpjs_kesehatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->dropColumn([
                'nomor_kontrak',
                'contract_type',
                'durasi_probation_bulan',
                'tanggal_selesai_probation',
                'notice_period_hari',
                'jam_kerja_mingguan',
                'no_bpjs_ketenagakerjaan',
                'no_bpjs_kesehatan',
                'working_scope'
            ]);
        });
    }
};
