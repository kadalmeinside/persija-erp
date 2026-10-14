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
        if (!Schema::hasTable('tbl_contract_templates')) {
            Schema::create('tbl_contract_templates', function (Blueprint $table) {
                $table->id();
                $table->string('nama_template');
                $table->string('contract_type', 50)->index(); // PKWT, PKWTT, Professional, Freelance, Internship
                $table->string('judul_surat')->default('SURAT PERJANJIAN KERJA');
                $table->longText('konten'); // Konten pasal-pasal dengan placeholder {TAG}
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('tbl_karyawan') && !Schema::hasColumn('tbl_karyawan', 'custom_contract_content')) {
            Schema::table('tbl_karyawan', function (Blueprint $table) {
                $table->longText('custom_contract_content')->nullable()->after('working_scope');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_karyawan') && Schema::hasColumn('tbl_karyawan', 'custom_contract_content')) {
            Schema::table('tbl_karyawan', function (Blueprint $table) {
                $table->dropColumn('custom_contract_content');
            });
        }

        Schema::dropIfExists('tbl_contract_templates');
    }
};
