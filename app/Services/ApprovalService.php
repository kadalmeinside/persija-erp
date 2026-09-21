<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PengajuanStatus;
use App\Enums\PengajuanType;
use App\Models\ApprovalProcess;
use App\Models\ApprovalRule;
use App\Models\InvoiceHeader;
use App\Models\PengajuanHeader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApprovalService
{
    // =========================================================================
    // HELPER: Deteksi tipe model yang masuk
    // =========================================================================

    /**
     * Kembalikan metadata model: tipe string, ID pengaju, dan nominal dokumen.
     *
     * @return array{tipe: string, pengajuId: int|null, amount: float, isCuti: bool, isPinjaman: bool, isInvoice: bool}
     */
    private function detectModel($model): array
    {
        if ($model instanceof PengajuanHeader) {
            return [
                'tipe'       => 'Pengajuan',
                'pengajuId'  => $model->id_pengaju,
                'amount'     => (float) $model->total_nominal_diajukan,
                'isCuti'     => false,
                'isPinjaman' => false,
                'isInvoice'  => false,
            ];
        }

        if ($model instanceof \App\Models\PengajuanCuti) {
            return [
                'tipe'       => 'Cuti',
                'pengajuId'  => $model->id_karyawan,
                'amount'     => 0,
                'isCuti'     => true,
                'isPinjaman' => false,
                'isInvoice'  => false,
            ];
        }

        if ($model instanceof \App\Models\Pinjaman) {
            return [
                'tipe'       => 'Pinjaman',
                'pengajuId'  => $model->id_karyawan,
                'amount'     => (float) $model->jumlah_pinjaman,
                'isCuti'     => false,
                'isPinjaman' => true,
                'isInvoice'  => false,
            ];
        }

        if ($model instanceof InvoiceHeader) {
            return [
                'tipe'       => 'Invoice',
                'pengajuId'  => null, // Invoice dibuat Finance, tidak ada "pengaju karyawan" yang bisa conflict
                'amount'     => (float) $model->total_tagihan,
                'isCuti'     => false,
                'isPinjaman' => false,
                'isInvoice'  => true,
            ];
        }

        throw new \InvalidArgumentException(
            'ApprovalService::detectModel() — Tipe model tidak dikenali: ' . get_class($model) . '. '
            . 'Daftarkan model ini di detectModel() jika ingin menggunakan approval workflow.'
        );
    }

    /**
     * Helper: Buat filter query ApprovalProcess berdasarkan tipe model.
     */
    private function buildQuery($model, $meta): \Illuminate\Database\Eloquent\Builder
    {
        $query = ApprovalProcess::query();

        if ($meta['isCuti'])     return $query->where('id_cuti', $model->id);
        if ($meta['isPinjaman']) return $query->where('id_pinjaman', $model->id);
        if ($meta['isInvoice'])  return $query->where('id_invoice', $model->id);

        return $query->where('id_pengajuan', $model->id);
    }

    /**
     * Helper: Buat data FK untuk ApprovalProcess::create().
     */
    private function buildFkData($model, $meta): array
    {
        return [
            'id_pengajuan' => (!$meta['isCuti'] && !$meta['isPinjaman'] && !$meta['isInvoice']) ? $model->id : null,
            'id_cuti'      => $meta['isCuti']     ? $model->id : null,
            'id_pinjaman'  => $meta['isPinjaman'] ? $model->id : null,
            'id_invoice'   => $meta['isInvoice']  ? $model->id : null,
        ];
    }

    /**
     * Helper: Cari User notifiable dari Karyawan ID.
     */
    private function getUserNotifiable($karyawanId)
    {
        $karyawan = \App\Models\Karyawan::find($karyawanId);
        return $karyawan?->user;
    }

    // =========================================================================
    // INIT APPROVAL
    // =========================================================================

    public function initApproval($model): void
    {
        Log::info('--- START INIT APPROVAL ---');

        // detectModel() akan throw InvalidArgumentException jika model tidak dikenali
        $meta = $this->detectModel($model);

        Log::info("Type: {$meta['tipe']}, ID: {$model->id}, Pengaju ID: {$meta['pengajuId']}, Amount: {$meta['amount']}");

        $departemenId = $this->resolveDepartemenId($model, $meta);

        $rules = ApprovalRule::where('id_departemen', $departemenId)
                    ->where('tipe', $meta['tipe'])
                    ->orderBy('level_order', 'asc')
                    ->get();

        Log::info("Jumlah Rule Ditemukan ({$meta['tipe']}): " . $rules->count());

        if ($rules->isEmpty()) {
            Log::warning("⚠️ TIDAK ADA RULE untuk dept {$departemenId} tipe {$meta['tipe']}!");

            // Invoice tanpa rule → tetap Draft (tidak auto-approve)
            if ($meta['isInvoice']) {
                Log::warning('Invoice tanpa ApprovalRule — tetap berstatus Draft. Konfigurasikan ApprovalRule tipe Invoice.');
                return;
            }
            return;
        }

        $fkData         = $this->buildFkData($model, $meta);
        $firstActivated = false;
        $allSkipped     = true;
        $documentAmount = $meta['amount'];

        foreach ($rules as $rule) {
            $isSelfApproval    = ($meta['pengajuId'] !== null && $rule->id_karyawan_approver == $meta['pengajuId']);
            $isBelowThreshold  = ($rule->min_amount !== null && $documentAmount < (float) $rule->min_amount);

            if ($isSelfApproval || $isBelowThreshold) {
                $reason = $isBelowThreshold
                    ? "Auto-skipped: Nominal (Rp " . number_format($documentAmount, 0, ',', '.') . ") di bawah threshold (Rp " . number_format($rule->min_amount, 0, ',', '.') . ")"
                    : 'Auto-skipped: Approver adalah Pengaju (Segregation of Duties)';

                Log::warning("⚠️ Auto-Skip Level {$rule->level_order}: {$reason}");

                ApprovalProcess::create(array_merge($fkData, [
                    'level_order'        => $rule->level_order,
                    'id_karyawan_target' => $rule->id_karyawan_approver,
                    'label_aksi'         => $rule->label_aksi,
                    'status'             => 'Skipped',
                    'catatan'            => $reason,
                ]));
            } else {
                $status = $firstActivated ? 'Waiting' : 'Pending';

                ApprovalProcess::create(array_merge($fkData, [
                    'level_order'        => $rule->level_order,
                    'id_karyawan_target' => $rule->id_karyawan_approver,
                    'label_aksi'         => $rule->label_aksi,
                    'status'             => $status,
                ]));

                if (!$firstActivated) {
                    $approverUser = $this->getUserNotifiable($rule->id_karyawan_approver);
                    if ($approverUser) {
                        $approverUser->notify(new \App\Notifications\PengajuanPendingNotification($model));
                    }
                    $firstActivated = true;
                }

                $allSkipped = false;
            }
        }

        // Semua step di-skip → auto-approve
        if ($allSkipped) {
            Log::warning('⚠️ SEMUA step di-skip. Auto-Approved.');
            $this->finalizeApproval($model, $meta, null);
            Log::info('--- END INIT APPROVAL (AUTO-APPROVED) ---');
            return;
        }

        // Set status Pending Approval
        $this->setPendingStatus($model, $meta);
        Log::info('--- END INIT APPROVAL (SUKSES) ---');
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approve($model, $karyawanIdAction, $catatan = null): void
    {
        DB::transaction(function () use ($model, $karyawanIdAction, $catatan) {
            Log::info("User {$karyawanIdAction} mencoba approve Model ID {$model->id}");

            $meta = $this->detectModel($model);

            $currentStep = $this->buildQuery($model, $meta)
                               ->where('status', 'Pending')
                               ->orderBy('level_order', 'asc')
                               ->first();

            if (!$currentStep) {
                throw new \Exception('Tidak ada persetujuan yang pending untuk dokumen ini.');
            }

            // IDENTITY GUARD
            if ($currentStep->id_karyawan_target != $karyawanIdAction) {
                Log::warning("Identity mismatch: target={$currentStep->id_karyawan_target}, actor={$karyawanIdAction}");
                throw new \Exception('Anda tidak berwenang menyetujui langkah ini. Silakan hubungi approver yang ditunjuk.');
            }

            // Update step saat ini
            $currentStep->update([
                'status'             => 'Approved',
                'id_karyawan_action' => $karyawanIdAction,
                'tgl_aksi'           => Carbon::now(),
                'catatan'            => $catatan,
                'uuid'               => (string) Str::uuid(),
            ]);

            // Bersihkan notifikasi approver ini
            $currentUser = $this->getUserNotifiable($karyawanIdAction);
            if ($currentUser) {
                $this->clearNotification($currentUser, $model->id, $meta['tipe']);
            }

            // Cek step berikutnya (lewati Skipped)
            $nextStep = $this->buildQuery($model, $meta)
                            ->where('level_order', '>', $currentStep->level_order)
                            ->whereNotIn('status', ['Skipped'])
                            ->orderBy('level_order', 'asc')
                            ->first();

            if ($nextStep) {
                $nextStep->update(['status' => 'Pending']);
                Log::info("Lanjut ke Level: {$nextStep->level_order}");

                $approverUser = $this->getUserNotifiable($nextStep->id_karyawan_target);
                if ($approverUser) {
                    $approverUser->notify(new \App\Notifications\PengajuanPendingNotification($model));
                }
            } else {
                // Final Approval
                $this->finalizeApproval($model, $meta, $karyawanIdAction);
                Log::info('Approval Selesai. Status: Final Approved.');
            }
        });
    }

    // =========================================================================
    // REJECT
    // =========================================================================

    public function reject($model, $karyawanIdAction, $catatan): void
    {
        DB::transaction(function () use ($model, $karyawanIdAction, $catatan) {
            Log::info("User {$karyawanIdAction} menolak Model ID {$model->id}");

            $meta = $this->detectModel($model);

            $currentStep = $this->buildQuery($model, $meta)
                               ->where('status', 'Pending')
                               ->first();

            if (!$currentStep) {
                throw new \Exception('Tidak ada persetujuan yang pending untuk dokumen ini.');
            }

            // IDENTITY GUARD
            if ($currentStep->id_karyawan_target != $karyawanIdAction) {
                throw new \Exception('Anda tidak berwenang menolak langkah ini.');
            }

            $currentStep->update([
                'status'             => 'Rejected',
                'id_karyawan_action' => $karyawanIdAction,
                'tgl_aksi'           => Carbon::now(),
                'catatan'            => $catatan,
            ]);

            $currentUser = $this->getUserNotifiable($karyawanIdAction);
            if ($currentUser) {
                $this->clearNotification($currentUser, $model->id, $meta['tipe']);
            }

            $this->setRejectedStatus($model, $meta);
        });
    }

    // =========================================================================
    // REVISION (Minta Perbaikan)
    // =========================================================================

    public function revision($model, $karyawanIdAction, $catatan): void
    {
        DB::transaction(function () use ($model, $karyawanIdAction, $catatan) {
            Log::info("User {$karyawanIdAction} meminta revisi Model ID {$model->id}");

            $meta = $this->detectModel($model);

            $currentStep = $this->buildQuery($model, $meta)
                               ->where('status', 'Pending')
                               ->first();

            if (!$currentStep) {
                throw new \Exception('Tidak ada persetujuan yang pending untuk dokumen ini.');
            }

            // IDENTITY GUARD
            if ($currentStep->id_karyawan_target != $karyawanIdAction) {
                throw new \Exception('Anda tidak berwenang meminta revisi pada langkah ini.');
            }

            $currentStep->update([
                'status'             => 'Revision',
                'id_karyawan_action' => $karyawanIdAction,
                'tgl_aksi'           => Carbon::now(),
                'catatan'            => $catatan,
            ]);

            $currentUser = $this->getUserNotifiable($karyawanIdAction);
            if ($currentUser) {
                $this->clearNotification($currentUser, $model->id, $meta['tipe']);
            }

            $this->setRevisionStatus($model, $meta);

            // Notifikasi pengaju
            $pengajuKaryawanId = $meta['isCuti'] || $meta['isPinjaman']
                ? $model->id_karyawan
                : ($meta['isInvoice'] ? null : $model->id_pengaju);

            if ($pengajuKaryawanId) {
                $pengajuUser = $this->getUserNotifiable($pengajuKaryawanId);
                if ($pengajuUser) {
                    $pengajuUser->notify(new \App\Notifications\PengajuanPendingNotification($model));
                }
            }

            Log::info('Revision requested. Status set to REVISION.');
        });
    }

    // =========================================================================
    // PRIVATE: Final Approval Handler
    // =========================================================================

    /**
     * Finalisasi dokumen setelah semua langkah approve.
     *
     * @param int|null $karyawanIdAction null jika auto-approve
     */
    private function finalizeApproval($model, array $meta, ?int $karyawanIdAction): void
    {
        if ($meta['isCuti']) {
            $model->update(['status' => 'Approved']);
            $this->deductLeaveBalance($model);
            return;
        }

        if ($meta['isPinjaman']) {
            $model->update([
                'status'        => PengajuanStatus::APPROVED->value,
                'approved_by'   => $karyawanIdAction,
                'approved_at'   => now(),
                'approval_uuid' => (string) Str::uuid(),
            ]);
            $approverKaryawan = $karyawanIdAction ? \App\Models\Karyawan::find($karyawanIdAction) : null;
            app(\App\Services\PinjamanService::class)->createPaymentRequest($model, $approverKaryawan);
            return;
        }

        if ($meta['isInvoice']) {
            // Invoice: update approved_by/at dan serahkan GL posting ke InvoiceService
            $model->update([
                'approved_by' => $karyawanIdAction,
                'approved_at' => now(),
            ]);
            app(\App\Services\InvoiceService::class)->postGLAfterApproval($model);
            return;
        }

        // PengajuanHeader — cek apakah tipe PettyCash
        if ($model instanceof PengajuanHeader) {
            $isPettyCash = $model->tipe_pengajuan === \App\Enums\PengajuanType::PETTY_CASH->value;

            if ($isPettyCash) {
                // PettyCash: langsung post GL & set PAID (uang sudah keluar secara fisik)
                app(\App\Services\PengajuanService::class)->postPettyCashGL($model);
                return;
            }

            // PengajuanHeader tipe lain (Langsung, UangMuka, Reimburse): set APPROVED
            $model->update(['status_global' => PengajuanStatus::APPROVED]);
            return;
        }

        // Fallback
        $model->update(['status_global' => PengajuanStatus::APPROVED]);
    }

    // =========================================================================
    // PRIVATE: Status Setters
    // =========================================================================

    private function setPendingStatus($model, array $meta): void
    {
        if ($meta['isCuti'])     { $model->update(['status' => 'Pending']); return; }
        if ($meta['isPinjaman']) { $model->update(['status' => PengajuanStatus::PENDING_APPROVAL->value]); return; }
        if ($meta['isInvoice'])  {
            // Invoice tetap 'Draft' selama pending approval — sudah jelas di UI
            return;
        }
        $model->update(['status_global' => PengajuanStatus::PENDING_APPROVAL]);
    }

    private function setRejectedStatus($model, array $meta): void
    {
        if ($meta['isCuti'])     { $model->update(['status' => 'Rejected']); return; }
        if ($meta['isPinjaman']) { $model->update(['status' => PengajuanStatus::REJECTED->value]); return; }
        if ($meta['isInvoice'])  { $model->update(['status' => InvoiceStatus::Cancelled]); return; }
        $model->update(['status_global' => PengajuanStatus::REJECTED]);
    }

    private function setRevisionStatus($model, array $meta): void
    {
        if ($meta['isCuti'])     { $model->update(['status' => 'Pending']); return; } // Cuti tidak punya REVISION
        if ($meta['isPinjaman']) { $model->update(['status' => PengajuanStatus::REVISION->value]); return; }
        if ($meta['isInvoice'])  {
            // Invoice: kembali ke Draft agar bisa diedit
            $model->update(['status' => InvoiceStatus::Draft]);
            return;
        }
        $model->update(['status_global' => PengajuanStatus::REVISION]);
    }

    // =========================================================================
    // PRIVATE: Resolve Departemen ID dari model
    // =========================================================================

    private function resolveDepartemenId($model, array $meta): ?int
    {
        if ($meta['isInvoice'])  return $model->id_departemen;
        if (!$meta['isCuti'] && !$meta['isPinjaman']) return $model->id_departemen;
        return $model->karyawan?->id_departemen;
    }

    // =========================================================================
    // PRIVATE: Utilities
    // =========================================================================

    private function clearNotification($user, $modelId, $modelType): void
    {
        if (!$user) return;

        $user->unreadNotifications
             ->filter(function ($notification) use ($modelId, $modelType) {
                 return isset($notification->data['id'])
                     && $notification->data['id'] == $modelId
                     && isset($notification->data['type'])
                     && $notification->data['type'] == $modelType;
             })
             ->markAsRead();
    }

    private function deductLeaveBalance(\App\Models\PengajuanCuti $cuti): void
    {
        $currentYear = date('Y');
        $saldo = \App\Models\SaldoCuti::where('id_karyawan', $cuti->id_karyawan)
                    ->where('id_jenis_cuti', $cuti->id_jenis_cuti)
                    ->where('tahun_periode', $currentYear)
                    ->first();

        if ($saldo) {
            $saldo->update([
                'saldo_terpakai' => $saldo->saldo_terpakai + $cuti->jumlah_hari,
                'saldo_akhir'    => $saldo->saldo_awal - ($saldo->saldo_terpakai + $cuti->jumlah_hari),
            ]);
            Log::info('Saldo Cuti dikurangi. Sisa: ' . $saldo->saldo_akhir);
        }
    }
}