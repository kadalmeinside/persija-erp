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
        // 1. Komponen Gaji (Master Data)
        Schema::create('tbl_gaji_komponen', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nama_komponen', 100);
            $table->enum('tipe', ['pendapatan', 'potongan']); // Income (Allowance) or Deduction
            $table->boolean('is_taxable')->default(true);
            $table->foreignId('id_akun_gl')->constrained('tbl_akun_gl'); // Expense Account (for income) or Liability Account (for deduction)
            $table->timestamps();
        });

        // 2. Payroll Header (Per Periode)
        Schema::create('tbl_payroll', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('bulan_periode', 7); // YYYY-MM
            $table->date('tgl_payroll');
            $table->decimal('total_gaji_kotor', 15, 2);
            $table->decimal('total_potongan', 15, 2);
            $table->decimal('total_gaji_bersih', 15, 2);
            $table->string('status', 50)->default('Draft'); // Draft, Approved, Paid
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 3. Payroll Detail (Per Karyawan)
        Schema::create('tbl_payroll_detail', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('id_payroll')->constrained('tbl_payroll')->onDelete('cascade');
            $table->foreignId('id_karyawan')->constrained('tbl_karyawan');
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('total_tunjangan', 15, 2);
            $table->decimal('total_potongan', 15, 2);
            $table->decimal('gaji_bersih', 15, 2);
            $table->json('rincian_komponen'); // JSON storing specific component values for snapshot
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('tbl_payroll_detail');
        Schema::dropIfExists('tbl_payroll');
        Schema::dropIfExists('tbl_gaji_komponen');
        Schema::enableForeignKeyConstraints();
    }
};
