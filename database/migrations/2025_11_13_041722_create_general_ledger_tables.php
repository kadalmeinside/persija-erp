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
        
        Schema::create('tbl_jurnal_header', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            $table->string('nomor_jurnal', 100)->unique();
            $table->date('tgl_jurnal');
            $table->string('deskripsi_jurnal', 255);
            $table->enum('sumber_modul', ['AP', 'AR', 'FA', 'HR', 'INV', 'GL']);
            $table->unsignedBigInteger('id_referensi_sumber')->nullable(); 
            $table->string('source_type', 100)->nullable();
            $table->string('source_event', 50)->nullable();
            $table->uuid('posting_batch_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('tbl_jurnal_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB'; 
            $table->id(); 
            $table->foreignId('id_jurnal')->constrained('tbl_jurnal_header')->onDelete('cascade');
            $table->foreignId('id_akun')->constrained('tbl_akun_gl');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE tbl_jurnal_detail ADD CONSTRAINT jurnal_debit_nonnegative_check CHECK (debit >= 0)');
        DB::statement('ALTER TABLE tbl_jurnal_detail ADD CONSTRAINT jurnal_kredit_nonnegative_check CHECK (kredit >= 0)');
        DB::statement('ALTER TABLE tbl_jurnal_detail ADD CONSTRAINT jurnal_one_side_check CHECK (debit = 0 OR kredit = 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_jurnal_detail');
        Schema::dropIfExists('tbl_jurnal_header');
        Schema::enableForeignKeyConstraints();
    }
};