<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPayrollData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:reset
                            {--force : Skip confirmation prompt (use with caution!)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset semua data Payroll, Jurnal Payroll, dan Pengajuan Payroll (DESTRUCTIVE — hanya untuk development/recovery)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('⚠️  PERINGATAN: Perintah ini akan menghapus SEMUA data payroll secara permanen!');
        $this->line('  - tbl_payroll_detail');
        $this->line('  - tbl_payroll');
        $this->line('  - Jurnal dengan sumber_modul HR/PY');
        $this->line('  - Pengajuan dengan nomor PAY-*');
        $this->newLine();

        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('❌ Perintah ini diblokir di environment PRODUCTION.');
            $this->line('   Gunakan --force jika benar-benar diperlukan.');
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->confirm('Apakah Anda yakin ingin melanjutkan?', false)) {
                $this->info('Operasi dibatalkan.');
                return self::SUCCESS;
            }
        }

        $this->info('Memulai reset data payroll...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // 1. Truncate Payroll Tables
            DB::table('tbl_payroll_detail')->truncate();
            DB::table('tbl_payroll')->truncate();
            $this->line('✅ Truncated tbl_payroll_detail & tbl_payroll.');

            // 2. Delete Payroll Journals
            $journalIds = DB::table('tbl_jurnal_header')
                ->where('sumber_modul', 'HR')
                ->orWhere('sumber_modul', 'PY') // Handle legacy
                ->pluck('id');

            if ($journalIds->isNotEmpty()) {
                DB::table('tbl_jurnal_detail')->whereIn('id_jurnal', $journalIds)->delete();
                DB::table('tbl_jurnal_header')->whereIn('id', $journalIds)->delete();
                $this->line("✅ Deleted {$journalIds->count()} Payroll Journal(s).");
            } else {
                $this->line('ℹ️  Tidak ada Payroll Journal yang ditemukan.');
            }

            // 3. Delete Payroll Payment Requests
            // Support both old format (PAY-XXXXXXXX) and new format (PAY/YYYYMM/NNNN)
            $pengajuanIds = DB::table('tbl_pengajuan_header')
                ->where(function ($q) {
                    $q->where('nomor_pengajuan', 'like', 'PAY-%')   // format lama
                      ->orWhere('nomor_pengajuan', 'like', 'PAY/%'); // format baru
                })
                ->pluck('id');


            if ($pengajuanIds->isNotEmpty()) {
                DB::table('tbl_pengajuan_detail')->whereIn('id_pengajuan', $pengajuanIds)->delete();
                DB::table('tbl_pengajuan_header')->whereIn('id', $pengajuanIds)->delete();
                $this->line("✅ Deleted {$pengajuanIds->count()} Payroll Payment Request(s).");
            } else {
                $this->line('ℹ️  Tidak ada Payroll Payment Request yang ditemukan.');
            }

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->newLine();
        $this->info('✅ Payroll Reset selesai.');

        return self::SUCCESS;
    }
}
