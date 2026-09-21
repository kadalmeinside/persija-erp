<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class AccountingPeriodController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountingPeriod::with('closer')->orderBy('tanggal_mulai', 'desc');

        if ($request->search) {
            $query->where('nama_periode', 'like', '%' . $request->search . '%');
        }

        $periods = $query->paginate(12)->withQueryString();

        return Inertia::render('Admin/Accounting/Period/Index', [
            'periods' => $periods,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_periode' => 'required|string|max:100',
            'tanggal_mulai' => 'required|date|unique:tbl_accounting_periods,tanggal_mulai',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        AccountingPeriod::create([
            'nama_periode' => $request->nama_periode,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'is_closed' => false
        ]);

        return redirect()->back()->with('success', 'Periode akuntansi berhasil dibuat.');
    }

    public function close(AccountingPeriod $period)
    {
        if ($period->is_closed) {
            return redirect()->back()->with('error', 'Periode sudah ditutup.');
        }

        // Optional: Check if all previous periods are closed?
        // For now, allow independent closing but warn user in UI.

        $period->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => Auth::id()
        ]);

        return redirect()->back()->with('success', 'Periode berhasil ditutup (Closed).');
    }

    public function reopen(AccountingPeriod $period)
    {
        if (!$period->is_closed) {
            return redirect()->back()->with('error', 'Periode belum ditutup.');
        }

        // Only Admin/Finance Manager should do this (Middleware check later)

        $period->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null
        ]);

        return redirect()->back()->with('success', 'Periode berhasil dibuka kembali (Reopened).');
    }
}
