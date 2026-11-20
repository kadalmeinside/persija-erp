<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class GenerateMissingUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:generate-for-karyawan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate user account (login) untuk karyawan yang belum memiliki akun (user_id = null)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $karyawans = Karyawan::whereNull('user_id')->get();

        if ($karyawans->isEmpty()) {
            $this->info("Semua karyawan sudah memiliki akun User.");
            return;
        }

        $this->info("Menemukan {$karyawans->count()} karyawan yang belum memiliki akun User. Memulai pembuatan...");

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($karyawans as $karyawan) {
                // Bersihkan NIK dari spasi/karakter aneh untuk email
                $cleanNik = preg_replace('/[^a-zA-Z0-9]/', '', $karyawan->nomor_induk_karyawan);
                
                // Gunakan nama depan untuk email jika NIK tidak valid, tapi defaultnya NIK
                // Permintaan user: "ambil nama pertamanya lalu nomor urut saja, jangan gunakan nik"
                // Mari kita buat email dari nama depan + nomor acak / id
                $namaParts = explode(' ', trim($karyawan->nama_lengkap));
                $namaDepan = strtolower(preg_replace('/[^a-zA-Z]/', '', $namaParts[0]));
                
                // Jika nama depan kosong, fallback ke NIK
                if (empty($namaDepan)) {
                    $namaDepan = 'user';
                }

                // Format Email: namadepan.id@persijadevelopment.id
                $email = "{$namaDepan}.{$karyawan->id}@persijadevelopment.id";

                $tempPassword = \Illuminate\Support\Str::password(12, symbols: false);

                // Buat User
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $karyawan->nama_lengkap,
                        'password' => Hash::make($tempPassword),
                        'pin' => null,
                        'must_change_password' => true,
                        'must_change_pin' => true,
                    ]
                );

                // Berikan Role 'Karyawan'
                if (method_exists($user, 'hasRole') && !$user->hasRole('Karyawan')) {
                    $user->assignRole('Karyawan');
                }

                // Update Karyawan dengan user_id
                $karyawan->update(['user_id' => $user->id]);

                $this->line("[OK] Akun dibuat: {$karyawan->nama_lengkap} -> {$email} (Password sementara: {$tempPassword})");
                $count++;
            }

            DB::commit();
            $this->info("Berhasil membuat {$count} akun user baru. Semua user wajib mengganti password dan mengatur PIN saat login pertama.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Terjadi kesalahan: " . $e->getMessage());
        }
    }
}
