<?php

namespace App\Http\Controllers\Admin\Pengajuan;

use App\Http\Controllers\Controller;
use App\Models\PengajuanHeader;
use App\Enums\PengajuanStatus;
use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use iio\libmergepdf\Merger;
use Illuminate\Support\Facades\Response;

use App\Models\Setting; // Added import

class ReportController extends Controller
{
    /**
     * My Requests (Pengajuan Saya)
     */
    public function myRequests(Request $request)
    {
        $karyawan = Auth::user()->karyawan;
        if (!$karyawan) abort(403, 'User tidak terhubung ke data Karyawan.');

        $query = PengajuanHeader::with(['pengaju', 'departemen', 'laporanPenggunaan'])
                    ->where('id_pengaju', $karyawan->id);

        if ($request->search) {
            $query->where('nomor_pengajuan', 'like', '%' . $request->search . '%');
        }

        if ($request->status) {
            $query->where('status_global', $request->status);
        }

        if ($request->tipe) {
            $query->where('tipe_pengajuan', $request->tipe);
        }

        if ($request->laporan) {
            if ($request->laporan === 'butuh') {
                $query->butuhLaporan();
            } elseif ($request->laporan === 'ada') {
                $query->whereHas('laporanPenggunaan');
            }
        }

        $perPage = $request->per_page ? (int)$request->per_page : 15;
        if ($perPage > 100) $perPage = 100; // Limit max pagination
        $pengajuan = $query->orderBy('tgl_pengajuan', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Pengajuan/Index', [
            'pengajuan' => $pengajuan,
            'filters' => $request->only(['search', 'status', 'tipe', 'laporan', 'per_page']),
            'activeTab' => 'my-requests'
        ]);
    }

    /**
     * All Requests (Finance/Admin View)
     */
    public function allRequests(Request $request)
    {
        if (!Auth::user()->hasRole(Role::financeRoles())) {
             return redirect()->route('admin.pengajuan.my-requests');
        }

        $query = PengajuanHeader::with(['pengaju', 'departemen']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nomor_pengajuan', 'like', '%' . $request->search . '%')
                  ->orWhereHas('pengaju', function($q2) use ($request) {
                      $q2->where('nama_lengkap', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->status) {
            $query->where('status_global', $request->status);
        }

        $perPage = $request->per_page ? (int)$request->per_page : 15;
        if ($perPage > 100) $perPage = 100; // Limit max pagination
        $pengajuan = $query->orderBy('tgl_pengajuan', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Pengajuan/Index', [
            'pengajuan' => $pengajuan,
            'filters' => $request->only(['search', 'status', 'tipe', 'laporan', 'per_page']),
            'activeTab' => 'all-requests'
        ]);
    }

    /**
     * Print PDF (Single)
     * Previously named 'print' in PengajuanController
     */
    public function print(PengajuanHeader $pengajuan)
    {
        $user = Auth::user();
        if (!$user->hasRole(Role::financeRoles()) && $pengajuan->id_pengaju !== $user->karyawan?->id) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak dokumen ini.');
        }

        $pengajuan->load([
            'pengaju', 'departemen', 'detail.akunGl', 'detail.programKerja',
            'approvalProcess.approver', 'pembayaran'
        ]);
        
        // Fetch Company Settings
        $settings = Setting::all()->pluck('value', 'key');
        
        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            // Convert to absolute filesystem path for DomPDF
            $logoPath = storage_path('app/public/' . $settings['company_logo']);
            // Fallback if file doesn't exist locally (e.g. S3), use URL if needed, but local is best for performance
            if (!file_exists($logoPath)) {
                $logoPath = null; 
            }
        }

        $company = [
            'name' => $settings['company_name'] ?? 'PERSIJA JAYA JAKARTA',
            'address' => $settings['company_address'] ?? 'Rasuna Office Park, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        // Attachment Logic
        $attachmentPath = null;
        $isImage = false;
        
        if ($pengajuan->attachment_path) {
            $absPath = storage_path('app/public/' . $pengajuan->attachment_path);
            if (file_exists($absPath)) {
                $attachmentPath = $absPath;
                $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $isImage = true;
                }
            }
        }

        $pdf = Pdf::loadView('admin.pengajuan.print', [
            'pengajuan' => $pengajuan,
            'timestamp' => now()->format('d/m/Y H:i'),
            'company' => $company,
            'attachmentPath' => $attachmentPath,
            'isImage' => $isImage
        ]);

        $safeName = str_replace(['/', '\\'], '-', $pengajuan->nomor_pengajuan);
        
        $merger = new Merger;
        $merger->addRaw($pdf->output());

        if ($attachmentPath && !$isImage && strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION)) === 'pdf') {
            $merger->addFile($attachmentPath);
        }

        try {
            $mergedPdfContent = $merger->merge();
            return Response::make($mergedPdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Pengajuan_' . $safeName . '.pdf"'
            ]);
        } catch (\Exception $e) {
            // Fallback to original if merge fails
            return $pdf->stream('Pengajuan_' . $safeName . '.pdf');
        }
    }
    /**
     * Print PDF Voucher Gabungan (100% Digital)
     */
    public function printVoucher(PengajuanHeader $pengajuan)
    {
        $user = Auth::user();
        if (!$user->hasRole(Role::financeRoles()) && $pengajuan->id_pengaju !== $user->karyawan?->id) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak dokumen ini.');
        }

        // Hanya bisa dicetak jika sudah dibayar
        if (!in_array($pengajuan->status_global, [PengajuanStatus::PAID, PengajuanStatus::SETTLED])) {
            return redirect()->back()->with('error', 'Voucher Pembayaran hanya dapat dicetak setelah pengajuan dibayar (Paid/Settled).');
        }

        $pengajuan->load([
            'pengaju', 'departemen', 'detail.akunGl', 'detail.programKerja',
            'approvalProcess.approver', 'approvalProcess.actionKaryawan', 
            'pembayaran.kasBank', 'vendorPenerima', 'karyawanPenerima',
            'laporanPenggunaan.detail.akunGl',
            'laporanPenggunaan.detail.programKerja',
            'laporanPenggunaan.verifier'
        ]);
        
        // Fetch Company Settings
        $settings = Setting::all()->pluck('value', 'key');
        
        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            $logoPath = storage_path('app/public/' . $settings['company_logo']);
            if (!file_exists($logoPath)) {
                $logoPath = null; 
            }
        }

        $company = [
            'name' => $settings['company_name'] ?? 'PERSIJA JAYA JAKARTA',
            'address' => $settings['company_address'] ?? 'Rasuna Office Park, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        // Attachment Logic (Awal)
        $attachmentPath = null;
        $isImage = false;
        
        if ($pengajuan->attachment_path) {
            $absPath = storage_path('app/public/' . $pengajuan->attachment_path);
            if (file_exists($absPath)) {
                $attachmentPath = $absPath;
                $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $isImage = true;
                }
            }
        }

        // Bukti Transfer Logic
        $buktiPembayaran = [];
        foreach ($pengajuan->pembayaran as $bayar) {
            if ($bayar->bukti_bayar_path) {
                $absPath = storage_path('app/public/' . $bayar->bukti_bayar_path);
                if (file_exists($absPath)) {
                    $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                    $buktiPembayaran[] = [
                        'path' => $absPath,
                        'is_image' => $isImg,
                        'filename' => basename($bayar->bukti_bayar_path)
                    ];
                }
            }
        }

        // Laporan Penggunaan Bukti Logic
        $buktiLaporan = [];
        if ($pengajuan->laporanPenggunaan) {
            foreach ($pengajuan->laporanPenggunaan->detail as $item) {
                if ($item->bukti_path) {
                    $absPath = storage_path('app/public/' . $item->bukti_path);
                    if (file_exists($absPath)) {
                        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
                        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                        $buktiLaporan[] = [
                            'path' => $absPath,
                            'is_image' => $isImg,
                            'filename' => basename($item->bukti_path),
                            'deskripsi' => $item->deskripsi_bon,
                            'nominal' => $item->nominal_bon
                        ];
                    }
                }
            }
        }

        $pdf = Pdf::loadView('admin.pengajuan.print-voucher', [
            'pengajuan' => $pengajuan,
            'timestamp' => now()->format('d/m/Y H:i'),
            'company' => $company,
            'attachmentPath' => $attachmentPath,
            'isImage' => $isImage,
            'buktiPembayaran' => $buktiPembayaran,
            'buktiLaporan' => $buktiLaporan
        ]);

        $safeName = str_replace(['/', '\\'], '-', $pengajuan->nomor_pengajuan);
        
        $merger = new Merger;
        $merger->addRaw($pdf->output());

        if ($attachmentPath && !$isImage && strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION)) === 'pdf') {
            $merger->addFile($attachmentPath);
        }

        foreach ($buktiPembayaran as $bukti) {
            if (!$bukti['is_image'] && strtolower(pathinfo($bukti['path'], PATHINFO_EXTENSION)) === 'pdf') {
                $merger->addFile($bukti['path']);
            }
        }

        foreach ($buktiLaporan as $bukti) {
            if (!$bukti['is_image'] && strtolower(pathinfo($bukti['path'], PATHINFO_EXTENSION)) === 'pdf') {
                $merger->addFile($bukti['path']);
            }
        }

        try {
            $mergedPdfContent = $merger->merge();
            return Response::make($mergedPdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Voucher_' . $safeName . '.pdf"'
            ]);
        } catch (\Exception $e) {
            // Fallback
            return $pdf->stream('Voucher_' . $safeName . '.pdf');
        }
    }
}
