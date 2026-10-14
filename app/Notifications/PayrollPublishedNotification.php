<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PayrollPublishedNotification extends Notification
{
    public string $bulanPeriode;

    public function __construct(string $bulanPeriode)
    {
        $this->bulanPeriode = $bulanPeriode;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ess',
            'title' => 'Slip Gaji Telah Tersedia',
            'message' => "Slip gaji Anda untuk periode {$this->bulanPeriode} telah dipublikasikan dan siap dilihat di ESS.",
            'link' => '/ess?tab=payslip',
            'bulan_periode' => $this->bulanPeriode,
        ];
    }
}
