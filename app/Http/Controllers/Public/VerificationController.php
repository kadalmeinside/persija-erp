<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProcess;
use App\Models\Pinjaman;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function verify($uuid)
    {
        // 0. Check Pemohon Cuti (Encrypted ID)
        if (str_starts_with($uuid, 'CUTI-')) {
            try {
                $id = decrypt(substr($uuid, 5));
                $cuti = \App\Models\PengajuanCuti::with('karyawan')->findOrFail($id);
                $data = [
                    'isValid' => true,
                    'approverName' => $this->maskName($cuti->karyawan->nama_lengkap),
                    'approvedAt' => $cuti->created_at->format('d F Y H:i'),
                    'requestType' => 'Pengajuan Cuti (Pemohon)',
                    'requestNo' => 'CUTI-' . date('Y', strtotime($cuti->created_at)) . '-' . str_pad($cuti->id, 5, '0', STR_PAD_LEFT),
                    'status' => 'Submitted'
                ];
                return Inertia::render('Public/Verification', ['verification' => $data]);
            } catch (\Exception $e) {
                // fall through to fail
            }
        }

        // 1. Check ApprovalProcess (for Cuti & PengajuanHeader)
        $approval = ApprovalProcess::with(['actionKaryawan', 'pengajuan', 'cuti', 'cuti.karyawan'])
            ->where('uuid', $uuid)
            ->first();

        if ($approval) {
            $refNo = $approval->id_cuti 
                ? ($approval->cuti->karyawan->nama_lengkap . ' (Cuti)') 
                : $approval->pengajuan->nomor_pengajuan;

            $data = [
                'isValid' => true,
                'approverName' => $this->maskName($approval->actionKaryawan->nama_lengkap ?? 'Unknown'),
                'approvedAt' => $approval->tgl_aksi ? $approval->tgl_aksi->format('d F Y H:i') : '-',
                'requestType' => $approval->id_cuti ? 'Pengajuan Cuti' : 'Pengajuan Dana',
                'requestNo' => $this->maskRef($refNo),
                'status' => $approval->status
            ];

            return Inertia::render('Public/Verification', [
                'verification' => $data
            ]);
        }

        // 2. Check Pinjaman
        $pinjaman = Pinjaman::with(['approver.karyawan', 'karyawan'])
            ->where('approval_uuid', $uuid)
            ->first();

        if ($pinjaman) {
            $approverName = $pinjaman->approver->karyawan->nama_lengkap ?? $pinjaman->approver->name ?? 'Unknown';
            $refNo = 'Pinjaman: ' . $pinjaman->karyawan->nama_lengkap;

            $data = [
                'isValid' => true,
                'approverName' => $this->maskName($approverName),
                'approvedAt' => $pinjaman->approved_at ? $pinjaman->approved_at->format('d F Y H:i') : '-',
                'requestType' => 'Pinjaman Karyawan',
                'requestNo' => $this->maskRef($refNo),
                'status' => $pinjaman->status
            ];

            return Inertia::render('Public/Verification', [
                'verification' => $data
            ]);
        }

        // 3. Not Found
        return Inertia::render('Public/Verification', [
            'verification' => ['isValid' => false]
        ]);
    }

    private function maskName($name)
    {
        if (!$name) return '-';
        $parts = explode(' ', $name);
        $maskedParts = array_map(function ($part) {
            if (strlen($part) <= 2) return $part; // Too short to mask
            return substr($part, 0, 1) . str_repeat('*', strlen($part) - 2) . substr($part, -1);
        }, $parts);
        return implode(' ', $maskedParts);
    }

    private function maskRef($ref)
    {
        if (!$ref) return '-';
        // If it looks like a Name (contains spaces), modify masking or just use maskName behavior?
        // Let's assume generic masking: show first 4, mask middle, show last 2
        
        $len = strlen($ref);
        if ($len <= 5) return $ref;

        return substr($ref, 0, 4) . '****' . substr($ref, -3);
    }
}
