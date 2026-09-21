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
        Schema::create('tbl_periode_closing', function (Blueprint $table) {
            $table->id();
            $table->integer('bulan');
            $table->integer('tahun');
            $table->enum('status', ['Open', 'Closed'])->default('Open');
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->foreignId('reopened_by')->nullable()->constrained('users');
            $table->string('catatan')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();

            // Sinergi 1 bulan = 1 tahun unik
            $table->unique(['bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_periode_closing');
    }
};
