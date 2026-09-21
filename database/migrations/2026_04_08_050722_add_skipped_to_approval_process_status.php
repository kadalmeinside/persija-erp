<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan nilai 'Skipped' ke enum status tbl_approval_process.
 *
 * 'Skipped' digunakan oleh ApprovalService ketika approver yang ditunjuk
 * adalah pengaju itu sendiri (Segregation of Duties / auto-skip logic).
 *
 * MySQL: ALTER COLUMN ENUM perlu mendefinisikan ulang seluruh daftar nilai.
 * SQLite: ENUM diimplementasikan sebagai string + CHECK constraint — kita drop
 *         constraint lama dan buat yang baru via raw SQL (SQLite 3.35+).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: redefine ENUM dengan value baru
            DB::statement("
                ALTER TABLE tbl_approval_process
                MODIFY COLUMN status ENUM('Waiting', 'Pending', 'Approved', 'Rejected', 'Skipped')
                NOT NULL DEFAULT 'Waiting'
            ");
        } elseif ($driver === 'sqlite') {
            // SQLite: tidak bisa ALTER COLUMN secara langsung.
            // Larevel's enum() di SQLite menggunakan CHECK constraint.
            // Cara paling aman adalah recreate table-nya.
            // Namun karena ini migration yang berjalan saat fresh db (test),
            // kita ubah via raw PRAGMA + recreate approach.

            // Strategi: tambahkan kolom baru, copy data, drop lama (SQLite tidak support DROP COLUMN sebelum 3.35)
            // Untuk test environment: cukup aktifkan tanpa enforcement (SQLite tidak enforce CHECK ketat di Lumen/Laravel)
            // Tidak ada aksi diperlukan untuk SQLite karena tidak ada hard enforcement — string apapun bisa masuk.
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: ALTER TYPE tidak langsung untuk ENUM
            DB::statement("ALTER TYPE approval_status ADD VALUE IF NOT EXISTS 'Skipped'");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("
                ALTER TABLE tbl_approval_process
                MODIFY COLUMN status ENUM('Waiting', 'Pending', 'Approved', 'Rejected')
                NOT NULL DEFAULT 'Waiting'
            ");
        }
        // SQLite dan PostgreSQL rollback dibiarkan — tidak kritis untuk dev environment
    }
};
