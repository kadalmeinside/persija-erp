<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_karyawan_biometrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->unique()->constrained('tbl_karyawan')->onDelete('cascade');
            $table->text('face_descriptor')->nullable();
            $table->timestamps();
        });

        // Migrate data from tbl_karyawan to tbl_karyawan_biometrics, encrypting it along the way
        $karyawans = DB::table('tbl_karyawan')->whereNotNull('face_descriptor')->get(['id', 'face_descriptor']);
        foreach ($karyawans as $karyawan) {
            DB::table('tbl_karyawan_biometrics')->insert([
                'id_karyawan' => $karyawan->id,
                // Encrypt the face_descriptor
                'face_descriptor' => Crypt::encryptString($karyawan->face_descriptor),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Drop the old column
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->dropColumn('face_descriptor');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_karyawan', function (Blueprint $table) {
            $table->text('face_descriptor')->nullable()->after('foto');
        });

        // Decrypt and copy back
        $biometrics = DB::table('tbl_karyawan_biometrics')->whereNotNull('face_descriptor')->get(['id_karyawan', 'face_descriptor']);
        foreach ($biometrics as $bio) {
            try {
                $decrypted = Crypt::decryptString($bio->face_descriptor);
                DB::table('tbl_karyawan')
                    ->where('id', $bio->id_karyawan)
                    ->update(['face_descriptor' => $decrypted]);
            } catch (\Exception $e) {
                // If it fails, just ignore
            }
        }

        Schema::dropIfExists('tbl_karyawan_biometrics');
    }
};
