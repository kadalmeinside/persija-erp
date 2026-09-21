<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HariLibur;
use Carbon\Carbon;

class HariLiburSeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['tanggal' => '2025-01-01', 'keterangan' => 'Tahun Baru 2025 Masehi'],
            ['tanggal' => '2025-01-27', 'keterangan' => 'Isra Mikraj Nabi Muhammad SAW'],
            ['tanggal' => '2025-01-29', 'keterangan' => 'Tahun Baru Imlek 2576 Kongzili'],
            ['tanggal' => '2025-03-29', 'keterangan' => 'Hari Suci Nyepi Tahun Baru Saka 1947'],
            ['tanggal' => '2025-03-31', 'keterangan' => 'Idul Fitri 1446 Hijriah'],
            ['tanggal' => '2025-04-01', 'keterangan' => 'Idul Fitri 1446 Hijriah'],
            ['tanggal' => '2025-04-18', 'keterangan' => 'Wafat Yesus Kristus'],
            ['tanggal' => '2025-04-20', 'keterangan' => 'Kebangkitan Yesus Kristus (Paskah)'],
            ['tanggal' => '2025-05-01', 'keterangan' => 'Hari Buruh Internasional'],
            ['tanggal' => '2025-05-12', 'keterangan' => 'Hari Raya Waisak 2569 BE'],
            ['tanggal' => '2025-05-29', 'keterangan' => 'Kenaikan Yesus Kristus'],
            ['tanggal' => '2025-06-01', 'keterangan' => 'Hari Lahir Pancasila'],
            ['tanggal' => '2025-06-06', 'keterangan' => 'Idul Adha 1446 Hijriah'],
            ['tanggal' => '2025-06-27', 'keterangan' => 'Tahun Baru Islam 1447 Hijriah'],
            ['tanggal' => '2025-08-17', 'keterangan' => 'Hari Kemerdekaan Republik Indonesia'],
            ['tanggal' => '2025-09-05', 'keterangan' => 'Maulid Nabi Muhammad SAW'],
            ['tanggal' => '2025-12-25', 'keterangan' => 'Hari Raya Natal'],

            // 2026 Holidays
            ['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru 2026 Masehi'],
            ['tanggal' => '2026-02-14', 'keterangan' => 'Isra Mikraj Nabi Muhammad SAW'],
            ['tanggal' => '2026-02-17', 'keterangan' => 'Tahun Baru Imlek'],
            ['tanggal' => '2026-03-19', 'keterangan' => 'Hari Suci Nyepi'],
            ['tanggal' => '2026-03-20', 'keterangan' => 'Idul Fitri 1447 Hijriah'],
            ['tanggal' => '2026-03-21', 'keterangan' => 'Idul Fitri 1447 Hijriah'],
            ['tanggal' => '2026-04-03', 'keterangan' => 'Wafat Yesus Kristus'],
            ['tanggal' => '2026-05-01', 'keterangan' => 'Hari Buruh Internasional'],
            ['tanggal' => '2026-05-14', 'keterangan' => 'Kenaikan Yesus Kristus'],
            ['tanggal' => '2026-05-27', 'keterangan' => 'Idul Adha 1447 Hijriah'],
            ['tanggal' => '2026-05-31', 'keterangan' => 'Hari Raya Waisak'],
            ['tanggal' => '2026-06-01', 'keterangan' => 'Hari Lahir Pancasila'],
            ['tanggal' => '2026-06-16', 'keterangan' => 'Tahun Baru Islam 1448 Hijriah'],
            ['tanggal' => '2026-08-17', 'keterangan' => 'Hari Kemerdekaan Republik Indonesia'],
            ['tanggal' => '2026-08-25', 'keterangan' => 'Maulid Nabi Muhammad SAW'],
            ['tanggal' => '2026-12-25', 'keterangan' => 'Hari Raya Natal'],
        ];

        foreach ($holidays as $holiday) {
            HariLibur::firstOrCreate(
                ['tanggal' => $holiday['tanggal']],
                ['keterangan' => $holiday['keterangan']]
            );
        }

        $this->command->info('Data Hari Libur 2025 & 2026 berhasil dibuat.');
    }
}
