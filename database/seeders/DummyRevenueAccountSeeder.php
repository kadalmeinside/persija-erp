<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\AkunGl;

class DummyRevenueAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'kode_akun' => '4-1001',
                'nama_akun' => 'Pendapatan Jasa Konsultasi',
                'tipe_akun' => 'Pendapatan'
            ],
            [
                'kode_akun' => '4-1002',
                'nama_akun' => 'Pendapatan Penjualan Merchandise',
                'tipe_akun' => 'Pendapatan'
            ],
            [
                'kode_akun' => '4-1003',
                'nama_akun' => 'Pendapatan Tiket Pertandingan',
                'tipe_akun' => 'Pendapatan'
            ],
            [
                'kode_akun' => '4-1004',
                'nama_akun' => 'Pendapatan Sponsorship',
                'tipe_akun' => 'Pendapatan'
            ]
        ];

        foreach ($accounts as $account) {
            AkunGl::firstOrCreate(
                ['kode_akun' => $account['kode_akun']],
                $account
            );
        }

        $this->command->info('Dummy Revenue Accounts created successfully.');
    }
}
