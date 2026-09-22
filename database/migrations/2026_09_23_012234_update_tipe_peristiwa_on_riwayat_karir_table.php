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
        DB::statement("ALTER TABLE tbl_riwayat_karir MODIFY COLUMN tipe_peristiwa ENUM('Pengangkatan Awal', 'Perpanjangan Kontrak', 'Pengangkatan Tetap', 'Promosi', 'Demosi', 'Mutasi', 'Penyesuaian Gaji', 'Lainnya', 'Resign', 'PHK', 'Habis Kontrak') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tbl_riwayat_karir MODIFY COLUMN tipe_peristiwa ENUM('Pengangkatan Awal', 'Perpanjangan Kontrak', 'Pengangkatan Tetap', 'Promosi', 'Demosi', 'Mutasi', 'Penyesuaian Gaji', 'Lainnya') NOT NULL");
    }
};
