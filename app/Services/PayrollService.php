<?php

namespace App\Services;

use App\Enums\AngsuranStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayrollStatus;
use App\Enums\PengajuanStatus;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Karyawan;
use App\Models\GajiKomponen;
use App\Models\KasBank;
use App\Models\AkunGl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use App\Services\DocumentNumberService;
use App\Services\GLService;

class PayrollService
{
    /**
     * Generate Draft Payroll for a given period.
     */
    public function generatePayroll($bulanPeriode, $tglPayroll)
    {
        return DB::transaction(function () use ($bulanPeriode, $tglPayroll) {
            // 1. Create Header
            $payroll = Payroll::create([
                'bulan_periode'    => $bulanPeriode,
                'tahun'            => substr($bulanPeriode, 0, 4),
                'tgl_payroll'      => $tglPayroll,
                'status'           => PayrollStatus::DRAFT->value,
                'total_gaji_kotor' => 0,
                'total_potongan'   => 0,
                'total_gaji_bersih'=> 0,
                'id_user_pembuat'  => Auth::id(),
            ]);

            // 2. Get Standard Employees (Tetap & Kontrak) for Auto-Population
            $karyawans = Karyawan::whereIn('status_karyawan', ['Tetap', 'Kontrak'])->get();

            foreach ($karyawans as $karyawan) {
                $gajiPokok = $karyawan->gaji_pokok;
                $tunjangan = 0;
                $honorarium = 0;
                $lembur = 0;
                $potongan = 0;
                $rincian = [];

                $rincian[] = ['nama' => 'Gaji Pokok', 'tipe' => 'pendapatan', 'nilai' => $gajiPokok];
                
                // Check for Loan Installments (Pinjaman)
                $loanInstallment = \App\Models\AngsuranPinjaman::where('bulan_periode', $bulanPeriode)
                    ->where('status_bayar', AngsuranStatus::PENDING->value)
                    ->whereHas('pinjaman', function($q) use ($karyawan) {
                        $q->where('id_karyawan', $karyawan->id)->where('status', 'Approved');
                    })
                    ->first();

                if ($loanInstallment) {
                    $potongan += $loanInstallment->jumlah_angsuran;
                    $rincian[] = [
                        'nama' => 'Potongan Pinjaman', 
                        'tipe' => 'potongan', 
                        'nilai' => $loanInstallment->jumlah_angsuran,
                        'id_angsuran' => $loanInstallment->id
                    ];
                }

                $gajiBersih = $gajiPokok + $tunjangan + $honorarium + $lembur - $potongan;

                $detail = PayrollDetail::create([
                    'id_payroll' => $payroll->id,
                    'id_karyawan' => $karyawan->id,
                    'gaji_pokok' => $gajiPokok,
                    'total_tunjangan' => $tunjangan,
                    'honorarium' => $honorarium,
                    'jumlah_sesi' => 0,
                    'rate_per_sesi' => 0,
                    'lembur' => $lembur,
                    'total_potongan' => $potongan,
                    'gaji_bersih' => $gajiBersih,
                    'rincian_komponen' => $rincian,
                ]);

                // Link Installment to Payroll Detail
                if ($loanInstallment) {
                    $loanInstallment->update(['id_payroll_detail' => $detail->id]);
                }
            }

            // 3. Recalculate Header
            $this->recalculateHeader($payroll->id);

            return $payroll;
        });
    }

    /**
     * Approve Payroll and Post to GL.
     *
     * @param  Payroll  $payroll
     * @param  array    $allocations  (opsional) Override alokasi per-program dari UI.
     *                                Jika kosong, gunakan alokasi otomatis dari rincian komponen.
     */
    public function approvePayroll(Payroll $payroll, array $allocations = [])
    {
        // 1. Validasi Status
        if ($payroll->status !== PayrollStatus::DRAFT->value) {
            throw new \Exception("Status Payroll harus Draft untuk bisa disetujui.");
        }

        // 2. Load Relations
        $payroll->load(['details.karyawan.departemen', 'details.programKerja', 'details.komponenGaji']);

        DB::transaction(function () use ($payroll) {
            $totalGajiBersih = 0;
            $journalDetails = [];
            $paymentRequestDetails = [];

            // Grouping for Payment Request (By Program & Bank Account)
            $programAllocations = [];

            foreach ($payroll->details as $detail) {
                $totalGajiBersih += $detail->gaji_bersih;

                // Process Rincian Komponen (JSON)
                $components = $detail->rincian_komponen ?? [];
                
                // Ensure it's an array (in case of legacy data or null)
                if (!is_array($components)) {
                    $components = json_decode($components, true) ?? [];
                }
                
                if (empty($components)) {
                    // Fallback: Construct components from columns
                    if ($detail->gaji_pokok > 0) {
                        $components[] = [
                            'type' => 'Gaji Pokok',
                            'amount' => $detail->gaji_pokok,
                            'id_program' => null,
                            'id_komponen_gaji' => null
                        ];
                    }
                    if ($detail->total_tunjangan > 0) {
                         $components[] = [
                            'type' => 'Tunjangan',
                            'amount' => $detail->total_tunjangan,
                            'id_program' => null,
                            'id_komponen_gaji' => null
                        ];
                    }
                    if ($detail->honorarium > 0) {
                        $components[] = [
                            'type' => 'Honorarium',
                            'amount' => $detail->honorarium,
                            'id_program' => $detail->id_program,
                            'id_komponen_gaji' => $detail->id_komponen_gaji
                        ];
                    }
                     if ($detail->lembur > 0) {
                        $components[] = [
                            'type' => 'Lembur',
                            'amount' => $detail->lembur,
                            'id_program' => null,
                            'id_komponen_gaji' => null
                        ];
                    }
                }

                foreach ($components as $comp) {
                    $amount = $comp['nilai'] ?? $comp['amount'];
                    if ($amount <= 0) continue;

                    $idProgram = $comp['id_program'] ?? null;
                    $idKomponen = $comp['id_komponen_gaji'] ?? null;
                    
                    // Determine GL Account
                    $debitAccount = null;
                    if ($idKomponen) {
                        $komponen = \App\Models\GajiKomponen::find($idKomponen);
                        $debitAccount = $komponen ? $komponen->id_akun_gl : null;
                    }
                    
                    if (!$debitAccount) {
                        // Check Department specific expense account
                        $deptAccount = $detail->karyawan->departemen->id_akun_beban_gaji ?? null;
                        
                        if ($deptAccount) {
                            $debitAccount = $deptAccount;
                        } else {
                            $salaryExpenseId = Config::get('accounting.accounts.payroll.salary_expense');
                            $debitAccount = $salaryExpenseId;
                        }
                    }

                    // Add to GL Journal Details
                    if (!isset($journalDetails[$debitAccount])) {
                        $journalDetails[$debitAccount] = 0;
                    }
                    $journalDetails[$debitAccount] += $amount;

                    // Add to Payment Request Allocations
                    $progKey = $idProgram ?? 'General';
                    if (!isset($programAllocations[$progKey])) {
                        $programAllocations[$progKey] = [
                            'id_program' => $idProgram,
                            'amount' => 0,
                            'description' => $idProgram ? 'Payroll Allocation' : 'General Payroll'
                        ];
                    }
                    $programAllocations[$progKey]['amount'] += $amount;
                }
            }

            // 3. Create GL Journal
            $payableAccount = Config::get('accounting.accounts.salaries_payable');
            $taxAccount     = Config::get('accounting.accounts.tax_payable');

            if (!$payableAccount) {
                throw new \Exception('Konfigurasi akun GL untuk Payroll belum lengkap. Pastikan ACC_SALARIES_PAYABLE_ID sudah diset di .env.');
            }

            $glLines = [];
            foreach ($journalDetails as $accId => $amount) {
                $glLines[] = [
                    'id_akun'    => $accId,    // ← key sesuai GLService::createJournal()
                    'debit'      => $amount,
                    'kredit'     => 0,          // ← 'kredit', bukan 'credit'
                    'keterangan' => 'Payroll Expense Allocation', // ← 'keterangan', bukan 'deskripsi'
                ];
            }

            // Credit Tax/Potongan
            if ($payroll->total_potongan > 0 && $taxAccount) {
                 $glLines[] = [
                    'id_akun'    => $taxAccount,
                    'debit'      => 0,
                    'kredit'     => $payroll->total_potongan,
                    'keterangan' => 'Payroll Deductions (Tax/BPJS)',
                ];
            }

            // Credit Net Payable
            $glLines[] = [
                'id_akun'    => $payableAccount,
                'debit'      => 0,
                'kredit'     => $payroll->total_gaji_bersih,
                'keterangan' => 'Accrued Salaries Payable',
            ];

            // Posting GL — argumen sesuai signature GLService::createJournal():
            // ($date, $description, $details, $sourceModule, $sourceRefId, $transactionType)
            GLService::createJournal(
                $payroll->tgl_payroll,                          // $date
                'Payroll Period ' . $payroll->bulan_periode,   // $description
                $glLines,                                       // $details
                'PAYROLL',                                      // $sourceModule
                $payroll->id,                                   // $sourceRefId
                'Payroll'                                       // $transactionType
            );

            // 4. Create Payment Request
            $pengajuKaryawan = auth()->user()?->karyawan;

            // Guard: pastikan approver terhubung ke data Karyawan
            if (!$pengajuKaryawan) {
                throw new \Exception('User yang menyetujui payroll belum terhubung ke data Karyawan. Hubungkan user ke Karyawan di menu Manajemen User.');
            }

            $pengajuan = \App\Models\PengajuanHeader::create([
                'nomor_pengajuan'        => DocumentNumberService::payroll(),
                'judul_pengajuan'        => 'Payroll Payment: ' . $payroll->bulan_periode,
                'id_pengaju'             => $pengajuKaryawan->id,
                'id_departemen'          => $pengajuKaryawan->id_departemen,
                'tgl_pengajuan'          => now(),
                'tipe_pengajuan'         => 'OperasionalUmum',
                'total_nominal_diajukan' => $payroll->total_gaji_bersih,
                'status_global'          => PengajuanStatus::APPROVED,
                'catatan_header'         => 'Auto-generated dari Payroll yang disetujui. Periode: ' . $payroll->bulan_periode,
                'metode_pembayaran'      => PaymentMethod::TRANSFER,
            ]);

            foreach ($programAllocations as $alloc) {
                \App\Models\PengajuanDetail::create([
                    'id_pengajuan'   => $pengajuan->id,
                    'deskripsi_item' => $alloc['description'],
                    'nominal_item'   => $alloc['amount'],
                    'id_program'     => $alloc['id_program'],
                ]);
            }

            // 5. Update Status
            $payroll->update(['status' => PayrollStatus::APPROVED->value]);

            // 6. Mark Loan Installments as Paid
            $detailIds = $payroll->details->pluck('id');
            \App\Models\AngsuranPinjaman::whereIn('id_payroll_detail', $detailIds)
                ->where('status_bayar', AngsuranStatus::PENDING->value)
                ->update(['status_bayar' => AngsuranStatus::PAID->value]);
        });
        
        return $payroll;
    }
    public function recalculateHeader($payrollId)
    {
        $payroll = Payroll::find($payrollId);
        if (!$payroll) return;

        $totals = $payroll->details()
            ->selectRaw('SUM(gaji_pokok + total_tunjangan + honorarium + lembur) as total_kotor, SUM(total_potongan) as total_potongan, SUM(gaji_bersih) as total_bersih')
            ->first();

        $payroll->update([
            'total_gaji_kotor' => $totals->total_kotor ?? 0,
            'total_potongan' => $totals->total_potongan ?? 0,
            'total_gaji_bersih' => $totals->total_bersih ?? 0
        ]);
    }
}
