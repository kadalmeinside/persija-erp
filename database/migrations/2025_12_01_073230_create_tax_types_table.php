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
        Schema::create('tbl_tax_types', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('kode_pajak', 20)->unique(); // e.g., "PPN", "PPH23"
            $table->string('nama_pajak', 100); // e.g., "PPN 11%", "PPh 23 Jasa"
            $table->decimal('rate', 5, 2); // e.g., 11.00, 2.00
            $table->enum('tipe', ['PPN', 'PPh']); // PPN adds, PPh deducts
            $table->foreignId('id_akun_gl')->constrained('tbl_akun_gl'); // Link to GL Account
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_tax_types');
    }
};
