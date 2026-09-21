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
        // 1. Drop FK and Column in tbl_faktur_jual_detail
        if (Schema::hasColumn('tbl_faktur_jual_detail', 'id_pajak')) {
            Schema::table('tbl_faktur_jual_detail', function (Blueprint $table) {
                // Check if FK exists is hard in Laravel without raw SQL, 
                // but usually if column exists, we assume FK exists or we try-catch.
                // Safest way is to just drop column directly if we don't care about FK name, 
                // but Laravel requires dropping FK first.
                // Let's try to drop FK in a separate block with try-catch or just ignore error?
                // No, better to use array syntax for dropForeign which generates name automatically.
                try {
                    $table->dropForeign(['id_pajak']);
                } catch (\Exception $e) {
                    // Ignore if FK not found
                }
                $table->dropColumn('id_pajak');
            });
        }

        // 2. Drop FK and Column in tbl_laporan_detail
        if (Schema::hasColumn('tbl_laporan_detail', 'id_pajak')) {
            Schema::table('tbl_laporan_detail', function (Blueprint $table) {
                try {
                    $table->dropForeign(['id_pajak']);
                } catch (\Exception $e) {
                    // Ignore
                }
                $table->dropColumn('id_pajak');
            });
        }

        // 3. Drop tbl_pajak
        Schema::dropIfExists('tbl_pajak');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate tbl_pajak (simplified)
        Schema::create('tbl_pajak', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pajak');
            $table->decimal('persentase', 5, 2);
            $table->timestamps();
        });

        // 2. Add column back to tbl_faktur_jual_detail
        Schema::table('tbl_faktur_jual_detail', function (Blueprint $table) {
            $table->foreignId('id_pajak')->nullable()->constrained('tbl_pajak');
        });
    }
};
