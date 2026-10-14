<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PengajuanPendingNotification extends Notification
{
    public $pengajuan;
    public string $actionStatus;
    public ?string $catatan;

    /**
     * Create a new notification instance.
     *
     * @param mixed $pengajuan
     * @param string $actionStatus 'pending', 'approved', 'rejected'
     * @param string|null $catatan
     */
    public function __construct($pengajuan = null, string $actionStatus = 'pending', ?string $catatan = null)
    {
        $this->pengajuan = $pengajuan;
        $this->actionStatus = $actionStatus;
        $this->catatan = $catatan;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $rawType = $this->pengajuan ? class_basename($this->pengajuan) : 'General';
        $typeLabel = match ($rawType) {
            'PengajuanCuti' => 'Cuti',
            'Pinjaman' => 'Pinjaman',
            'InvoiceHeader' => 'Invoice',
            'PengajuanHeader' => 'Pengajuan Dana',
            default => $rawType,
        };

        $id = $this->pengajuan ? $this->pengajuan->id : null;
        $link = '/approvals';

        if ($this->actionStatus === 'approved') {
            $title = "Pengajuan {$typeLabel} Disetujui";
            $message = "Dokumen {$typeLabel} Anda telah disetujui sepenuhnya.";
            $link = ($rawType === 'PengajuanCuti') ? '/cuti' : '/approvals';
        } elseif ($this->actionStatus === 'rejected') {
            $title = "Pengajuan {$typeLabel} Ditolak";
            $catatanText = $this->catatan ? " Catatan: {$this->catatan}" : '';
            $message = "Dokumen {$typeLabel} Anda ditolak.{$catatanText}";
            $link = ($rawType === 'PengajuanCuti') ? '/cuti' : '/approvals';
        } else {
            $title = "Butuh Persetujuan: {$typeLabel}";
            $applicantName = $this->resolveApplicantName();
            $message = $applicantName
                ? "Pengajuan {$typeLabel} dari {$applicantName} butuh persetujuan Anda."
                : "Pengajuan {$typeLabel} butuh persetujuan Anda.";
            $link = '/approvals';
        }

        return [
            'id' => $id,
            'type' => 'approval',
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'status' => $this->actionStatus,
            'action_status' => $this->actionStatus,
            'document_type' => $rawType,
        ];
    }

    private function resolveApplicantName(): ?string
    {
        if (!$this->pengajuan) return null;
        if (isset($this->pengajuan->karyawan) && $this->pengajuan->karyawan) {
            return $this->pengajuan->karyawan->nama_lengkap ?? null;
        }
        if (isset($this->pengajuan->pengaju) && $this->pengajuan->pengaju) {
            return $this->pengajuan->pengaju->nama_lengkap ?? null;
        }
        return null;
    }
}

