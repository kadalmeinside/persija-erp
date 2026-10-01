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
        Schema::dropIfExists('tbl_faktur_jual_detail');
        Schema::dropIfExists('tbl_faktur_jual_header');
        Schema::dropIfExists('tbl_faktur_vendor');
        Schema::dropIfExists('tbl_penerimaan_bayar');
        Schema::dropIfExists('tbl_customer');
        Schema::dropIfExists('tbl_aset_depresiasi_log');
        Schema::dropIfExists('tbl_aset_master');
    }

    public function down(): void
    {
        // Tidak dapat di-reverse
    }
};
