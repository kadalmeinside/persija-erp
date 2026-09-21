<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeClosing;
use App\Services\PeriodClosingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class PeriodClosingController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->tahun ?? date('Y');

        // Build list of 12 months for the selected year
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $record = PeriodeClosing::with(['closedBy', 'reopenedBy'])
                ->where('bulan', $i)
                ->where('tahun', $year)
                ->first();

            $months[] = [
                'bulan' => $i,
                'nama_bulan' => Carbon::create()->month($i)->locale('id')->translatedFormat('F'),
                'tahun' => $year,
                'status' => $record ? $record->status : 'Open',
                'catatan' => $record ? $record->catatan : null,
                'closed_by_name' => $record && $record->closedBy ? $record->closedBy->name : null,
                'closed_at' => $record && $record->closed_at ? $record->closed_at->format('d M Y H:i') : null,
                'reopened_by_name' => $record && $record->reopenedBy ? $record->reopenedBy->name : null,
                'reopened_at' => $record && $record->reopened_at ? $record->reopened_at->format('d M Y H:i') : null,
            ];
        }

        return Inertia::render('Admin/Finance/Closing/Index', [
            'months' => $months,
            'selectedYear' => (int)$year,
            // Check if user has permission to reopen (e.g., specific role or exact permission we check later)
            'canReopen' => Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Finance Manager')
        ]);
    }

    public function close(Request $request)
    {
        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer',
            'catatan' => 'nullable|string'
        ]);

        try {
            PeriodClosingService::closePeriod($request->bulan, $request->tahun, $request->catatan);
            return redirect()->back()->with('success', 'Periode akuntansi berhasil ditutup.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reopen(Request $request)
    {
        // Only specific designated people can re-open.
        // As requested: "1 orang saja, seperti petty cash". We will enforce role Super Admin or Finance Manager for now, as defined in `canReopen` frontend.
        if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Finance Manager')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki otorisasi untuk membuka kembali periode yang sudah ditutup. Hubungi Super Admin atau Finance Manager.');
        }

        $request->validate([
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer',
            'catatan' => 'required|string|min:5' // Alasan wajib
        ]);

        try {
            PeriodClosingService::reopenPeriod($request->bulan, $request->tahun, $request->catatan);
            return redirect()->back()->with('success', 'Periode akuntansi berhasil dibuka kembali.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
