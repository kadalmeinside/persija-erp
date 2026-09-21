<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Note: MODIFY COLUMN untuk ENUM hanya berlaku di MySQL/MariaDB.
     * SQLite (digunakan di test environment) tidak mendukung sintaks ini
     * karena ia menyimpan semua tipe sebagai TEXT secara native.
     */
    public function up(): void
    {
        // Skip untuk SQLite — tidak diperlukan karena SQLite
        // memperlakukan semua kolom sebagai TEXT by default.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE tbl_invoice_header MODIFY COLUMN status ENUM('Draft', 'Unpaid', 'Partial', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE tbl_invoice_header MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Unpaid'");
    }
};
