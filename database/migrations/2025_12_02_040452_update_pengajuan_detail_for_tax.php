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
        Schema::table('tbl_pengajuan_detail', function (Blueprint $table) {
            // Drop old relation if exists
            if (Schema::hasColumn('tbl_pengajuan_detail', 'id_pajak')) {
                // Drop foreign key first (try catch or check constraint name if possible, but standard laravel way is dropForeign)
                // Since we don't know exact constraint name, we assume standard array syntax works or we just drop column
                // But dropping column with FK usually requires dropping FK first.
                // Let's try dropping column directly, if it fails we might need to be more specific.
                // Safest way:
                $table->dropForeign(['id_pajak']);
                $table->dropColumn('id_pajak');
            }

            // Add new columns
            $table->foreignId('id_tax_type')->nullable()->after('id_akun')->constrained('tbl_tax_types')->nullOnDelete();
            $table->decimal('rate_pajak', 5, 2)->default(0)->after('id_tax_type'); // Snapshot rate
            $table->decimal('nominal_pajak', 15, 2)->default(0)->after('rate_pajak');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_pengajuan_detail', function (Blueprint $table) {
            $table->dropForeign(['id_tax_type']);
            $table->dropColumn(['id_tax_type', 'rate_pajak', 'nominal_pajak']);
            
            // Restore old (optional, but good for rollback)
            $table->foreignId('id_pajak')->nullable()->constrained('tbl_pajak');
        });
    }
};
