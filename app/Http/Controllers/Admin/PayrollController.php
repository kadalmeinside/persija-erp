<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Karyawan;
use App\Models\GajiKomponen;
use App\Models\AkunGl;
use App\Services\PayrollService;
use App\Enums\PayrollStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        $query = Payroll::query()->with('approver');

        if ($request->search) {
            $query->where('bulan_periode', 'like', '%' . $request->search . '%');
        }

        $payrolls = $query->orderBy('bulan_periode', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Payroll/Index', [
            'payrolls' => $payrolls,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $programs = \App\Models\ProgramKerja::with('departemen:id,nama_departemen')->get(['id', 'nama_program', 'id_departemen']);
        return Inertia::render('Admin/Payroll/Create', [
            'programs' => $programs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bulan_periode' => 'required|date_format:Y-m',
            'tgl_payroll' => 'required|date',
        ]);

        try {
            $payroll = $this->payrollService->generatePayroll(
                $validated['bulan_periode'],
                $validated['tgl_payroll']
            );
            return redirect()->route('admin.payrolls.show', $payroll->id)
                ->with('success', 'Draft Payroll berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show(Payroll $payroll)
    {
        $payroll->load(['details.karyawan.departemen', 'programKerja', 'approver', 'activities.causer']);

        // ✅ Pre-load semua ProgramKerja sekaligus — hindari N+1 di dalam map()
        $allProgramsByDept = \App\Models\ProgramKerja::orderBy('nama_program')
            ->get(['id', 'nama_program', 'id_departemen'])
            ->groupBy('id_departemen');

        // Group details by department for allocation view
        $allocationGroups = $payroll->details->groupBy('karyawan.id_departemen')->map(function ($details, $deptId) use ($allProgramsByDept) {
            $deptName  = $details->first()->karyawan->departemen->nama_departemen ?? 'Unknown';
            $totalBersih = $details->sum('gaji_bersih');

            // ✅ Ambil dari collection yang sudah di-load — tidak ada query tambahan
            $programs = $allProgramsByDept->get($deptId, collect());

            return [
                'id_departemen'    => $deptId,
                'nama_departemen'  => $deptName,
                'total_gaji_bersih'=> $totalBersih,
                'programs'         => $programs->values(),
            ];
        })->values();

        // Get all active employees for manual addition
        $employees = \App\Models\Karyawan::whereIn('status_karyawan', ['Tetap', 'Kontrak', 'Freelance'])
            ->select('id', 'nama_lengkap', 'gaji_pokok')
            ->orderBy('nama_lengkap')
            ->get();

        // Get all payroll components for manual addition (e.g. Honorarium, Bonus)
        $komponenGaji = \App\Models\GajiKomponen::where('tipe', 'pendapatan')->get(['id', 'nama_komponen']);

        // Get all programs for manual allocation
        $allPrograms = \App\Models\ProgramKerja::with('departemen:id,nama_departemen')
            ->orderBy('id_departemen')
            ->get(['id', 'nama_program', 'id_departemen']);

        return Inertia::render('Admin/Payroll/Show', [
            'payroll'          => $payroll,
            'allocationGroups' => $allocationGroups,
            'employees'        => $employees,
            'komponenGaji'     => $komponenGaji,
            'programs'         => $allPrograms
        ]);
    }

    public function approve(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== PayrollStatus::DRAFT) {
            return back()->with('error', 'Payroll sudah disetujui sebelumnya.');
        }

        try {
            $this->payrollService->approvePayroll($payroll, $request->input('allocations', []));
            return back()->with('success', 'Payroll berhasil disetujui. Pengajuan pembayaran telah dibuat untuk Finance.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyetujui payroll: ' . $e->getMessage());
        }
    }

    public function destroy(Payroll $payroll)
    {
        if ($payroll->status !== PayrollStatus::DRAFT) {
            return back()->with('error', 'Hanya payroll berstatus Draft yang dapat dihapus.');
        }

        try {
            DB::transaction(function () use ($payroll) {
                $payroll->details()->delete();
                $payroll->delete();
            });
            
            return back()->with('success', 'Draft Payroll berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus payroll: ' . $e->getMessage());
        }
    }

    public function storeDetail(Request $request, Payroll $payroll)
    {
        if ($payroll->status !== PayrollStatus::DRAFT) {
            return back()->with('error', 'Payroll sudah diproses, tidak dapat diubah.');
        }

        $request->validate([
            'id_karyawan' => 'required|exists:tbl_karyawan,id',
            'honorarium' => 'required|numeric|min:0',
            'id_program' => 'nullable|exists:tbl_program_kerja,id',
            'id_komponen_gaji' => 'nullable|exists:tbl_gaji_komponen,id',
            'total_tunjangan' => 'nullable|numeric|min:0',
            'lembur' => 'nullable|numeric|min:0',
            'total_potongan' => 'nullable|numeric|min:0'
        ]);

        try {
            DB::transaction(function () use ($payroll, $request) {
                // Check if detail exists for this employee
                $detail = $payroll->details()->where('id_karyawan', $request->id_karyawan)->first();

                $newItem = [
                    'type' => 'Honorarium',
                    'amount' => $request->honorarium,
                    'id_program' => $request->id_program,
                    'id_komponen_gaji' => $request->id_komponen_gaji,
                    'keterangan' => 'Honorarium Tambahan'
                ];

                if ($detail) {
                    // Update existing detail
                    $currentRincian = $detail->rincian_komponen ?? [];
                    if (!is_array($currentRincian)) {
                         $currentRincian = json_decode($currentRincian, true) ?? [];
                    }
                    
                    // Append new item
                    $currentRincian[] = $newItem;

                    // Update totals
                    $detail->honorarium += $request->honorarium;
                    $detail->total_tunjangan += $request->total_tunjangan ?? 0;
                    $detail->lembur += $request->lembur ?? 0;
                    $detail->total_potongan += $request->total_potongan ?? 0;
                    
                    // Recalculate Net
                    $detail->gaji_bersih = $detail->gaji_pokok + $detail->total_tunjangan + $detail->honorarium + $detail->lembur - $detail->total_potongan;
                    
                    $detail->rincian_komponen = $currentRincian;
                    $detail->save();

                } else {
                    // Create new detail
                    $karyawan = \App\Models\Karyawan::find($request->id_karyawan);
                    $rincian = [$newItem];

                    $payroll->details()->create([
                        'id_karyawan' => $request->id_karyawan,
                        'gaji_pokok' => 0,
                        'total_tunjangan' => $request->total_tunjangan ?? 0,
                        'honorarium' => $request->honorarium,
                        'jumlah_sesi' => 0,
                        'rate_per_sesi' => 0,
                        'lembur' => $request->lembur ?? 0,
                        'total_potongan' => $request->total_potongan ?? 0,
                        'gaji_bersih' => ($request->honorarium + ($request->total_tunjangan ?? 0) + ($request->lembur ?? 0)) - ($request->total_potongan ?? 0),
                        'id_program' => null,
                        'id_komponen_gaji' => null,
                        'rincian_komponen' => $rincian,
                    ]);
                }

                // Recalculate Header
                $this->payrollService->recalculateHeader($payroll->id);
            });

            return back()->with('success', 'Penerima berhasil ditambahkan.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan penerima: ' . $e->getMessage());
        }
    }

    public function updateDetail(Request $request, Payroll $payroll, PayrollDetail $detail)
    {
        if ($payroll->status !== PayrollStatus::DRAFT) {
            return back()->with('error', 'Payroll sudah diproses, tidak dapat diubah.');
        }

        if ($detail->id_payroll !== $payroll->id) {
            return back()->with('error', 'Data tidak valid.');
        }

        $request->validate([
            'gaji_pokok' => 'required|numeric|min:0',
            'total_tunjangan' => 'required|numeric|min:0',
            'honorarium' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'total_potongan' => 'required|numeric|min:0'
        ]);

        try {
            DB::transaction(function () use ($payroll, $detail, $request) {
                // 1. Update Detail
                $gajiBersih = $request->gaji_pokok + $request->total_tunjangan + $request->honorarium + $request->lembur - $request->total_potongan;
                
                $detail->update([
                    'gaji_pokok' => $request->gaji_pokok,
                    'total_tunjangan' => $request->total_tunjangan,
                    'honorarium' => $request->honorarium,
                    'lembur' => $request->lembur,
                    'total_potongan' => $request->total_potongan,
                    'gaji_bersih' => $gajiBersih
                ]);

                // 2. Recalculate Header (Using Service for consistency)
                $this->payrollService->recalculateHeader($payroll->id);
            });

            return back()->with('success', 'Rincian gaji berhasil diperbarui.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    public function printPayslip(Payroll $payroll, PayrollDetail $detail)
    {
        if ($detail->id_payroll !== $payroll->id) {
            return abort(404);
        }

        $detail->load('karyawan.departemen');

        // Fetch Company Settings
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        
        $logoPath = null;
        if (isset($settings['company_logo']) && $settings['company_logo']) {
            $logoPath = storage_path('app/public/' . $settings['company_logo']);
            if (!file_exists($logoPath)) $logoPath = null;
        }

        $company = [
            'name'      => $settings['company_name'] ?? 'PERSIJA JAYA JAKARTA',
            'address'   => $settings['company_address'] ?? 'Rasuna Office Park, Kuningan, Jakarta Selatan',
            'logo_path' => $logoPath,
        ];

        // Format Rincian Komponen if needed
        $rincian = $detail->rincian_komponen;
        if (is_string($rincian)) {
            $rincian = json_decode($rincian, true);
        }
        $rincian = $rincian ?? [];

        $pendapatan_tambahan = 0;
        foreach ($rincian as $item) {
            if (isset($item['amount']) && $item['amount'] > 0) {
                $pendapatan_tambahan += $item['amount'];
            }
        }

        $pdf = \PDF::loadView('admin.payroll.payslip', [
            'payroll'  => $payroll,
            'detail'   => $detail,
            'karyawan' => $detail->karyawan,
            'rincian'  => $rincian,
            'company'  => $company,
            'tgl_cetak'=> now(),
            'pendapatan_tambahan' => $pendapatan_tambahan
        ]);

        $pdf->setPaper('a5', 'portrait');

        return $pdf->stream('Payslip_' . str_replace(' ', '_', $detail->karyawan->nama_lengkap) . '_' . $payroll->bulan_periode . '.pdf');
    }
}
