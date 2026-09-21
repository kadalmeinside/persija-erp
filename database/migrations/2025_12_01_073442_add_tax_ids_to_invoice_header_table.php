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
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->foreignId('id_tax_ppn')->nullable()->after('subtotal')->constrained('tbl_tax_types');
            $table->foreignId('id_tax_pph')->nullable()->after('id_tax_ppn')->constrained('tbl_tax_types');
            $table->decimal('pph_rate', 5, 2)->after('ppn_amount')->default(0);
            $table->decimal('pph_amount', 15, 2)->after('pph_rate')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->dropForeign(['id_tax_ppn']);
            $table->dropForeign(['id_tax_pph']);
            $table->dropColumn(['id_tax_ppn', 'id_tax_pph', 'pph_rate', 'pph_amount']);
        });
    }
};
