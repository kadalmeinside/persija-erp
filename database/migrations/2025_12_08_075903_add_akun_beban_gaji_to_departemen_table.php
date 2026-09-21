<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbl_departemen', function (Blueprint $table) {
            $table->unsignedBigInteger('id_akun_beban_gaji')->nullable()->after('nama_departemen');
            $table->foreign('id_akun_beban_gaji')->references('id')->on('tbl_akun_gl')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tbl_departemen', function (Blueprint $table) {
            $table->dropForeign(['id_akun_beban_gaji']);
            $table->dropColumn('id_akun_beban_gaji');
        });
    }
};
