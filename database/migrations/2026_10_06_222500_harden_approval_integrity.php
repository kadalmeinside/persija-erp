<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_approval_rules', function (Blueprint $table) {
            $table->unique(
                ['id_departemen', 'tipe', 'level_order'],
                'approval_rules_department_type_level_unique'
            );
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement(
                'ALTER TABLE tbl_approval_rules MODIFY id_karyawan_approver BIGINT UNSIGNED NOT NULL'
            );
            DB::statement(
                'ALTER TABLE tbl_approval_process MODIFY id_karyawan_target BIGINT UNSIGNED NOT NULL'
            );
        }

        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement(
                'ALTER TABLE tbl_approval_process ADD CONSTRAINT approval_process_one_document_chk CHECK (' .
                '(id_pengajuan IS NOT NULL) + (id_cuti IS NOT NULL) + ' .
                '(id_pinjaman IS NOT NULL) + (id_invoice IS NOT NULL) = 1)'
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'pgsql'], true)) {
            DB::statement(
                'ALTER TABLE tbl_approval_process DROP CONSTRAINT approval_process_one_document_chk'
            );
        }

        Schema::table('tbl_approval_rules', function (Blueprint $table) {
            $table->dropUnique('approval_rules_department_type_level_unique');
        });
    }
};
