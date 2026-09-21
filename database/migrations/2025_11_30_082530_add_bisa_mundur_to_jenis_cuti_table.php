<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('tbl_jenis_cuti', function (Blueprint $table) {
            $table->boolean('bisa_mundur')->default(false)->after('kuota_default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tbl_jenis_cuti', function (Blueprint $table) {
            $table->dropColumn('bisa_mundur');
        });
    }
};
