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
        Schema::create('tbl_jurnal_header', function (Blueprint $table) {
            $table->id('id_jurnal');
            $table->string('nomor_jurnal', 100)->unique();
            $table->date('tgl_jurnal');
            $table->string('deskripsi_jurnal', 255);
            $table->enum('sumber_modul', ['AP', 'AR', 'FA', 'HR', 'INV', 'GL']);
            $table->unsignedBigInteger('id_referensi_sumber')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_jurnal_detail', function (Blueprint $table) {
            $table->id('id_jurnal_detail');
            $table->foreignId('id_jurnal')->constrained('tbl_jurnal_header')->onDelete('cascade');
            $table->foreignId('id_akun')->constrained('tbl_akun_gl');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_ledger_tables');
    }
};
