<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tbl_jenis_cuti', function (Blueprint $table) {
            $table->boolean('is_unlimited')->default(false)->after('khusus_perempuan');
            $table->boolean('wajib_lampiran')->default(false)->after('is_unlimited');
        });

        Schema::table('tbl_pengajuan_cuti', function (Blueprint $table) {
            $table->string('lampiran_path')->nullable()->after('alasan');
        });

        // Seed data for Cuti Sakit -> Izin Sakit
        DB::table('tbl_jenis_cuti')
            ->where('nama_cuti', 'Cuti Sakit')
            ->update([
                'nama_cuti' => 'Izin Sakit',
                'bisa_mundur' => 1,
                'is_unlimited' => 1,
                'wajib_lampiran' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuti_tables', function (Blueprint $table) {
            //
        });
    }
};
