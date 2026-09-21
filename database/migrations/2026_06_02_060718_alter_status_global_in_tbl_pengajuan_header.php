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
        DB::statement("ALTER TABLE tbl_pengajuan_header MODIFY COLUMN status_global ENUM('Draft', 'Pending Approval', 'Approved', 'Paid', 'Verification', 'Rejected', 'Settled', 'Revision', 'Cancelled') NOT NULL DEFAULT 'Draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Peringatan: Saat rollback, jika ada data yang statusnya 'Revision' atau 'Cancelled', MySQL bisa error.
        // Sebaiknya update dulu sebelum downgrade jika benar-benar butuh rollback (meskipun jarang dilakukan di production).
        DB::statement("ALTER TABLE tbl_pengajuan_header MODIFY COLUMN status_global ENUM('Draft', 'Pending Approval', 'Approved', 'Paid', 'Verification', 'Rejected', 'Settled') NOT NULL DEFAULT 'Draft'");
    }
};
