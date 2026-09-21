<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_pengajuan_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengajuan')->constrained('tbl_pengajuan_header')->onDelete('cascade');
            
            $table->date('tgl_bayar');
            $table->decimal('nominal_bayar', 15, 2);
            $table->string('bukti_bayar_path')->nullable();
            $table->text('catatan')->nullable();

            // PERUBAHAN: Merujuk ke Tabel Kas Bank, bukan langsung ke GL
            // Ini lebih spesifik (tahu nomor rekening mana yang dipakai)
            $table->foreignId('id_kas_bank')->nullable()->constrained('tbl_kas_bank');
            
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        // 2. Update Enum Status di Header (Menambah status 'Partial')
        // Note: Mengubah enum di MySQL kadang tricky, tapi untuk development bisa pakai raw statement
        // Atau kita biarkan status 'Approved' tapi secara logika frontend mengecek:
        // Jika (Total Bayar > 0 && < Total Tagihan) => Partial
        // Jika (Total Bayar == Total Tagihan) => Paid
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_pengajuan_pembayaran');
    }
};