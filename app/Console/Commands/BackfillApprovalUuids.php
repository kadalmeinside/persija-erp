<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalProcess;
use App\Models\Pinjaman;
use Illuminate\Support\Str;
use App\Enums\PengajuanStatus;

class BackfillApprovalUuids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-approval-uuids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate UUIDs for existing approved records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of UUIDs...');

        // 1. Backfill ApprovalProcess
        $approvals = ApprovalProcess::where('status', 'Approved')
            ->whereNull('uuid')
            ->get();

        $this->info("Found {$approvals->count()} approvals to update.");

        foreach ($approvals as $approval) {
            $approval->update(['uuid' => (string) Str::uuid()]);
        }

        // 2. Backfill Pinjaman
        // Note: Check if using Enum value or string depending on what's in DB. service uses Enum->value.
        // We'll check both 'Approved' string and Enum value just in case.
        $pinjaman = Pinjaman::whereIn('status', ['Approved', PengajuanStatus::APPROVED->value])
            ->whereNull('approval_uuid')
            ->get();

        $this->info("Found {$pinjaman->count()} pinjaman to update.");

        foreach ($pinjaman as $p) {
            $p->update(['approval_uuid' => (string) Str::uuid()]);
        }

        $this->info('Backfill completed successfully.');
    }
}
