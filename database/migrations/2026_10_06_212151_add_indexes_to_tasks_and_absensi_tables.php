<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_tasks', function (Blueprint $table) {
            $table->index('id_karyawan_creator');
            $table->index('id_karyawan_assignee');
            $table->index('status');
        });

        Schema::table('tbl_absensi', function (Blueprint $table) {
            $table->index(['id_karyawan', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_tasks', function (Blueprint $table) {
            $table->dropIndex(['id_karyawan_creator']);
            $table->dropIndex(['id_karyawan_assignee']);
            $table->dropIndex(['status']);
        });

        Schema::table('tbl_absensi', function (Blueprint $table) {
            $table->dropIndex(['id_karyawan', 'tanggal']);
            $table->dropIndex(['tanggal']);
        });
    }
};
