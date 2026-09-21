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
            $table->decimal('subtotal', 15, 2)->after('status')->default(0);
            $table->decimal('ppn_rate', 5, 2)->after('subtotal')->default(11.00); // Default 11%
            $table->decimal('ppn_amount', 15, 2)->after('ppn_rate')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_invoice_header', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'ppn_rate', 'ppn_amount']);
        });
    }
};
