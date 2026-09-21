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
        // Using raw DB statement because changing ENUM values in Laravel can be tricky
        DB::statement("ALTER TABLE tbl_approval_rules MODIFY COLUMN tipe ENUM('Pengajuan', 'Cuti', 'Pinjaman', 'Invoice') DEFAULT 'Pengajuan'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tbl_approval_rules MODIFY COLUMN tipe ENUM('Pengajuan', 'Cuti', 'Pinjaman') DEFAULT 'Pengajuan'");
    }
};
