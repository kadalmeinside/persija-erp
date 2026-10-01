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
        Schema::table('users', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('tbl_invoice_header', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) { $table->softDeletes(); });
        Schema::table('tbl_jurnal_header', function (Blueprint $table) { $table->softDeletes(); });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('tbl_invoice_header', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('tbl_pengajuan_header', function (Blueprint $table) { $table->dropSoftDeletes(); });
        Schema::table('tbl_jurnal_header', function (Blueprint $table) { $table->dropSoftDeletes(); });
    }
};
