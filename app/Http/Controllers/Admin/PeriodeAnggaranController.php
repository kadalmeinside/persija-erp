<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeAnggaran;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;

class PeriodeAnggaranController extends Controller
{
    public function index(Request $request)
    {
        $query = PeriodeAnggaran::query();

        if ($request->search) {
            $query->where('nama_periode', 'like', '%' . $request->search . '%');
        }

        $periodes = $query->orderBy('tanggal_mulai', 'desc')->paginate(10);

        return Inertia::render('Admin/PeriodeAnggaran/Index', [
            'periodes' => $periodes,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'is_active' => 'boolean'
        ]);

        $this->validateOverlap($request->tanggal_mulai, $request->tanggal_selesai);

        if ($request->is_active) {
            // Deactivate other periods if this one is active
            PeriodeAnggaran::where('is_active', true)->update(['is_active' => false]);
        }

        PeriodeAnggaran::create($request->only(['nama_periode', 'tanggal_mulai', 'tanggal_selesai', 'is_active']));

        return redirect()->back()->with('success', 'Periode Anggaran berhasil dibuat.');
    }

    public function update(Request $request, PeriodeAnggaran $periodeAnggaran)
    {
        $request->validate([
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'is_active' => 'boolean'
        ]);

        $this->validateOverlap($request->tanggal_mulai, $request->tanggal_selesai, $periodeAnggaran->id);

        if ($request->is_active) {
            // Deactivate other periods if this one is active
            PeriodeAnggaran::where('id', '!=', $periodeAnggaran->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $periodeAnggaran->update($request->only(['nama_periode', 'tanggal_mulai', 'tanggal_selesai', 'is_active']));

        return redirect()->back()->with('success', 'Periode Anggaran berhasil diperbarui.');
    }

    public function destroy(PeriodeAnggaran $periodeAnggaran)
    {
        // Check if used in BudgetMaster
        if ($periodeAnggaran->budgets()->exists()) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus periode yang sudah memiliki data anggaran.');
        }

        $periodeAnggaran->delete();

        return redirect()->back()->with('success', 'Periode Anggaran berhasil dihapus.');
    }

    private function validateOverlap($start, $end, $ignoreId = null)
    {
        $query = PeriodeAnggaran::where(function ($q) use ($start, $end) {
            $q->whereBetween('tanggal_mulai', [$start, $end])
              ->orWhereBetween('tanggal_selesai', [$start, $end])
              ->orWhere(function ($q2) use ($start, $end) {
                  $q2->where('tanggal_mulai', '<=', $start)
                     ->where('tanggal_selesai', '>=', $end);
              });
        });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => 'Periode tanggal bertabrakan (overlap) dengan periode yang sudah ada.'
            ]);
        }
    }
}
