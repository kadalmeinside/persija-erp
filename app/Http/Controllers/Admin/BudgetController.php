<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetMaster;
use App\Models\BudgetDetail;
use App\Models\PeriodeAnggaran;
use App\Models\Departemen;
use App\Models\ProgramKerja;
use App\Models\AkunGl;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Eager load Dept via Pos -> Program
        $query = BudgetMaster::with(['periode', 'posAnggaran.programKerja.departemen', 'posAnggaran.akunGl']);

        // Filters
        if ($request->id_periode) {
            $query->where('id_periode_anggaran', $request->id_periode);
        } else {
            // Default to active period if not specified
            $activePeriod = PeriodeAnggaran::where('is_active', true)->first();
            if ($activePeriod) {
                $query->where('id_periode_anggaran', $activePeriod->id);
            }
        }

        if ($request->id_departemen) {
            // Filter by Program Owner Department
            $query->whereHas('posAnggaran.programKerja', function($q) use ($request) {
                $q->where('id_departemen', $request->id_departemen);
            });
        }

        if ($request->search) {
            $query->whereHas('posAnggaran.akunGl', function($q) use ($request) {
                $q->where('nama_akun', 'like', '%' . $request->search . '%')
                  ->orWhere('kode_akun', 'like', '%' . $request->search . '%');
            });
        }

        // Order by Hierarchy: Dept -> Program (via Pos) -> Account (via Pos)
        // Note: Sorting by related columns in Eloquent requires joins or closure sorting.
        // For simplicity/performance with pagination, we might just get() and sort collection if dataset is small,
        // OR join tables. Given current scale, let's keep it simple or just order by ID for now, 
        // but user expects hierarchy.
        // Let's do a simple get() then sort in memory or just basic ordering.
        // Ideally we should join, but let's stick to basic for now to avoid breaking things.
        $budgets = $query->get(); // We'll sort in Vue or here if needed.


        // Data for filters
        $periodes = PeriodeAnggaran::orderBy('tanggal_mulai', 'desc')->get();
        $departemens = Departemen::orderBy('nama_departemen')->get();

        // --- CHART DATA PREPARATION ---
        $chartData = [
            'pacing' => [],
            'allocation' => []
        ];

        // --- HEADER SUMMARY DATA ---
        $expenseSummary = [
            'total' => 0,
            'used' => 0,
            'remaining' => 0
        ];

        $revenueSummary = [
            'target' => 0,
            'realized' => 0,
            'achievement' => 0
        ];

        $selectedPeriodId = $request->id_periode ?? ($activePeriod->id ?? null);
        
        if ($selectedPeriodId) {
            $period = PeriodeAnggaran::find($selectedPeriodId);
            
            if ($period) {
                // 0. Header Summary Calculation
                $baseSummaryQuery = BudgetMaster::where('id_periode_anggaran', $selectedPeriodId);
                if ($request->id_departemen) {
                    $baseSummaryQuery->whereHas('posAnggaran.programKerja', function($q) use ($request) {
                        $q->where('id_departemen', $request->id_departemen);
                    });
                }
                
                // Expense Summary (Biaya)
                $expenseQuery = clone $baseSummaryQuery;
                $expenseStats = $expenseQuery->whereHas('posAnggaran.akunGl', function($q) {
                        $q->where('tipe_akun', '!=', 'Pendapatan');
                    })
                    ->selectRaw('sum(anggaran_total_tahun) as total, sum(anggaran_terikat_ytd) as terikat, sum(anggaran_realisasi_ytd) as realisasi')
                    ->first();

                if ($expenseStats) {
                    $expenseSummary['total'] = $expenseStats->total ?? 0;
                    $expenseSummary['used'] = ($expenseStats->terikat + $expenseStats->realisasi) ?? 0;
                    $expenseSummary['remaining'] = $expenseSummary['total'] - $expenseSummary['used'];
                }

                // Revenue Summary (Pendapatan)
                $revenueQuery = clone $baseSummaryQuery;
                $revenueStats = $revenueQuery->whereHas('posAnggaran.akunGl', function($q) {
                        $q->where('tipe_akun', 'Pendapatan');
                    })
                    ->selectRaw('sum(anggaran_total_tahun) as target, sum(anggaran_realisasi_ytd) as realisasi')
                    ->first();

                if ($revenueStats) {
                    $revenueSummary['target'] = $revenueStats->target ?? 0;
                    $revenueSummary['realized'] = $revenueStats->realisasi ?? 0;
                    $revenueSummary['achievement'] = ($revenueSummary['target'] > 0) 
                        ? ($revenueSummary['realized'] / $revenueSummary['target']) * 100 
                        : 0;
                }

                // --- CHART DATA GENERATION ---
                
                // 1. Expense Chart Data
                // Plan (Pacing) - Expense Only
                $expensePacingData = BudgetDetail::whereHas('header', function($q) use ($selectedPeriodId, $request) {
                    $q->where('id_periode_anggaran', $selectedPeriodId)
                      ->whereHas('posAnggaran.akunGl', function($sq) {
                          $sq->where('tipe_akun', '!=', 'Pendapatan');
                      });
                    if ($request->id_departemen) {
                        $q->whereHas('posAnggaran.programKerja', function($sq) use ($request) {
                            $sq->where('id_departemen', $request->id_departemen);
                        });
                    }
                })
                ->selectRaw('bulan, tahun, sum(nominal_pacing) as total')
                ->groupBy('tahun', 'bulan')
                ->get();

                // Actual - Expense (Pengajuan)
                $expenseActualQuery = \App\Models\PengajuanHeader::whereIn('status_global', ['Paid', 'Settled'])
                    ->whereBetween('tgl_pengajuan', [$period->tanggal_mulai, $period->tanggal_selesai]);
                
                if ($request->id_departemen) {
                    $expenseActualQuery->where('id_departemen_asal', $request->id_departemen);
                }

                $expenseActualData = $expenseActualQuery
                    ->selectRaw('MONTH(tgl_pengajuan) as bulan, YEAR(tgl_pengajuan) as tahun, sum(total_nominal_diajukan) as total')
                    ->groupBy('tahun', 'bulan')
                    ->get();

                // 2. Revenue Chart Data
                // Plan (Pacing) - Revenue Only
                $revenuePacingData = BudgetDetail::whereHas('header', function($q) use ($selectedPeriodId, $request) {
                    $q->where('id_periode_anggaran', $selectedPeriodId)
                      ->whereHas('posAnggaran.akunGl', function($sq) {
                          $sq->where('tipe_akun', 'Pendapatan');
                      });
                    if ($request->id_departemen) {
                        $q->whereHas('posAnggaran.programKerja', function($sq) use ($request) {
                            $sq->where('id_departemen', $request->id_departemen);
                        });
                    }
                })
                ->selectRaw('bulan, tahun, sum(nominal_pacing) as total')
                ->groupBy('tahun', 'bulan')
                ->get();

                // Actual - Revenue (Invoices)
                // Assuming Revenue is recognized on Invoice Date (Accrual)
                $revenueActualQuery = \App\Models\InvoiceHeader::where('status', '!=', 'Draft') // Exclude Draft
                    ->whereBetween('tgl_invoice', [$period->tanggal_mulai, $period->tanggal_selesai]);

                if ($request->id_departemen) {
                    $revenueActualQuery->where('id_departemen', $request->id_departemen);
                }

                $revenueActualData = $revenueActualQuery
                    ->selectRaw('MONTH(tgl_invoice) as bulan, YEAR(tgl_invoice) as tahun, sum(subtotal) as total') // Use subtotal (before tax)
                    ->groupBy('tahun', 'bulan')
                    ->get();

                // 3. Format Data for Chart.js
                $labels = [];
                $expensePlan = [];
                $expenseActual = [];
                $revenuePlan = [];
                $revenueActual = [];

                $start = Carbon::parse($period->tanggal_mulai);
                $end = Carbon::parse($period->tanggal_selesai);
                
                while ($start <= $end) {
                    $month = $start->month;
                    $year = $start->year;
                    $labels[] = $start->format('M Y');
                    
                    // Expense
                    $epItem = $expensePacingData->where('bulan', $month)->where('tahun', $year)->first();
                    $expensePlan[] = $epItem ? $epItem->total : 0;

                    $eaItem = $expenseActualData->where('bulan', $month)->where('tahun', $year)->first();
                    $expenseActual[] = $eaItem ? $eaItem->total : 0;

                    // Revenue
                    $rpItem = $revenuePacingData->where('bulan', $month)->where('tahun', $year)->first();
                    $revenuePlan[] = $rpItem ? $rpItem->total : 0;

                    $raItem = $revenueActualData->where('bulan', $month)->where('tahun', $year)->first();
                    $revenueActual[] = $raItem ? $raItem->total : 0;

                    $start->addMonth();
                }

                $chartData['combined'] = [
                    'labels' => $labels,
                    'datasets' => [
                        // Revenue (Pendapatan) - Green Theme
                        [
                            'label' => 'Target Pendapatan',
                            'backgroundColor' => '#86efac', // Green 300
                            'borderColor' => '#22c55e', // Green 500
                            'borderDash' => [5, 5],
                            'data' => $revenuePlan,
                            'fill' => false,
                            'tension' => 0.4,
                            'order' => 1
                        ],
                        [
                            'label' => 'Realisasi Pendapatan',
                            'backgroundColor' => 'rgba(34, 197, 94, 0.2)', // Green 500 transparent
                            'borderColor' => '#15803d', // Green 700
                            'data' => $revenueActual,
                            'fill' => true,
                            'tension' => 0.4,
                            'order' => 2
                        ],
                        // Expense (Biaya) - Orange/Red Theme
                        [
                            'label' => 'Limit Anggaran (Biaya)',
                            'backgroundColor' => '#fdba74', // Orange 300
                            'borderColor' => '#f97316', // Orange 500
                            'borderDash' => [5, 5],
                            'data' => $expensePlan,
                            'fill' => false,
                            'tension' => 0.4,
                            'order' => 3
                        ],
                        [
                            'label' => 'Realisasi Biaya',
                            'backgroundColor' => '#ef4444', // Red 500
                            'borderColor' => '#b91c1c', // Red 700
                            'data' => $expenseActual,
                            'fill' => false,
                            'tension' => 0.4,
                            'order' => 4
                        ]
                    ]
                ];

                // 2. Doughnut Chart: Allocation
                $deptLabels = [];
                $deptTotals = [];
                $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6366f1']; // Tailwind colors

                if ($request->id_departemen) {
                    // If Dept Selected: Show Allocation per PROGRAM
                    $allocationData = BudgetMaster::where('id_periode_anggaran', $selectedPeriodId)
                        ->whereHas('posAnggaran.programKerja', function($q) use ($request) {
                            $q->where('id_departemen', $request->id_departemen);
                        })
                        ->with('posAnggaran.programKerja')
                        ->get()
                        ->groupBy('posAnggaran.programKerja.nama_program')
                        ->map(function ($row) {
                            return $row->sum('anggaran_total_tahun');
                        });

                    foreach ($allocationData as $programName => $total) {
                        $deptLabels[] = $programName;
                        $deptTotals[] = $total;
                    }
                    $chartData['allocation_title'] = 'Alokasi per Program';
                } else {
                    // If No Dept Selected: Show Allocation per DEPARTEMEN (Program Owner)
                    $allocationData = BudgetMaster::where('id_periode_anggaran', $selectedPeriodId)
                        ->with('posAnggaran.programKerja.departemen')
                        ->get()
                        ->groupBy('posAnggaran.programKerja.departemen.nama_departemen')
                        ->map(function ($row) {
                            return $row->sum('anggaran_total_tahun');
                        });

                    foreach ($allocationData as $deptName => $total) {
                        $deptLabels[] = $deptName;
                        $deptTotals[] = $total;
                    }
                    $chartData['allocation_title'] = 'Alokasi per Departemen';
                }

                $chartData['allocation'] = [
                    'labels' => $deptLabels,
                    'datasets' => [
                        [
                            'backgroundColor' => array_slice($colors, 0, count($deptLabels)),
                            'data' => $deptTotals
                        ]
                    ]
                ];
            }
        }

        return Inertia::render('Admin/Budget/Index', [
            'budgets'        => $budgets,
            'periodes'       => $periodes,
            'departemens'    => $departemens,
            'filters'        => $request->only(['id_periode', 'id_departemen', 'search']),
            'activePeriodId' => $activePeriod->id ?? null,
            'chartData'      => $chartData,
            'expenseSummary' => $expenseSummary,
            'revenueSummary' => $revenueSummary,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $periodes = PeriodeAnggaran::where('is_active', true)->orderBy('tanggal_mulai', 'desc')->get();
        $departemens = Departemen::orderBy('nama_departemen')->get();

        return Inertia::render('Admin/Budget/Create', [
            'periodes' => $periodes,
            'departemens' => $departemens,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_periode_anggaran' => 'required|exists:tbl_periode_anggaran,id',
            // 'id_departemen' => 'required', // Not needed for BudgetMaster anymore
            'id_akun' => 'required|exists:tbl_pos_anggaran,id', // id_akun here is actually id_pos_anggaran
            'pacing' => 'required|array|min:1',
            'pacing.*.bulan' => 'required|integer|min:1|max:12',
            'pacing.*.tahun' => 'required|integer',
            'pacing.*.nominal' => 'required|numeric|min:0',
        ]);

        // Check uniqueness
        $exists = BudgetMaster::where('id_periode_anggaran', $request->id_periode_anggaran)
            ->where('id_pos_anggaran', $request->id_akun) 
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors(['id_akun' => 'Budget untuk kombinasi ini sudah ada. Silakan edit yang sudah ada.'])->withInput();
        }

        DB::transaction(function () use ($request) {
            // 1. Calculate Total
            $totalAnggaran = collect($request->pacing)->sum('nominal');

            // 2. Create Header
            $budget = BudgetMaster::create([
                'id_periode_anggaran' => $request->id_periode_anggaran,
                // 'id_departemen' => $request->id_departemen, // Removed
                'id_pos_anggaran' => $request->id_akun, // Mapping id_akun input to id_pos_anggaran
                'anggaran_total_tahun' => $totalAnggaran,
                'anggaran_terikat_ytd' => 0,
                'anggaran_realisasi_ytd' => 0,
            ]);

            // 3. Create Details
            foreach ($request->pacing as $item) {
                BudgetDetail::create([
                    'id_budget_master' => $budget->id,
                    'bulan' => $item['bulan'],
                    'tahun' => $item['tahun'],
                    'nominal_pacing' => $item['nominal'],
                ]);
            }
        });

        return redirect()->route('admin.budget.index')->with('success', 'Anggaran berhasil dialokasikan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BudgetMaster $budget)
    {
        $budget->load(['periode', 'posAnggaran.programKerja.departemen', 'posAnggaran.akunGl', 'details']);
        
        return Inertia::render('Admin/Budget/Edit', [
            'budget' => $budget,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BudgetMaster $budget)
    {
        $request->validate([
            'pacing' => 'required|array|min:1',
            'pacing.*.bulan' => 'required|integer|min:1|max:12',
            'pacing.*.tahun' => 'required|integer',
            'pacing.*.nominal' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $budget) {
            // 1. Update Details
            foreach ($request->pacing as $item) {
                BudgetDetail::updateOrCreate(
                    [
                        'id_budget_master' => $budget->id,
                        'bulan' => $item['bulan'],
                        'tahun' => $item['tahun']
                    ],
                    [
                        'nominal_pacing' => $item['nominal']
                    ]
                );
            }

            // 2. Recalculate Total
            $totalAnggaran = collect($request->pacing)->sum('nominal');
            
            // Validate against usage
            $used = $budget->anggaran_terikat_ytd + $budget->anggaran_realisasi_ytd;
            if ($totalAnggaran < $used) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'pacing' => 'Total anggaran tidak boleh kurang dari jumlah yang sudah terpakai (' . number_format($used) . ')'
                ]);
            }

            $budget->update([
                'anggaran_total_tahun' => $totalAnggaran
            ]);
        });

        return redirect()->route('admin.budget.index')->with('success', 'Anggaran berhasil diperbarui.');
    }

    /**
     * Import budgets from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            // Import file via Maatwebsite Excel
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\BudgetImport, $request->file('file'));

            return redirect()->route('admin.budget.index')->with('success', 'Data anggaran berhasil di-import.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = 'Baris ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }
            return redirect()->back()->with('error', 'Gagal import: ' . implode(' | ', $messages));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    /**
     * Reset budgets for a specific period
     */
    public function resetPeriod(Request $request)
    {
        $request->validate([
            'id_periode' => 'required|exists:tbl_periode_anggaran,id',
            'force' => 'nullable|boolean'
        ]);

        $periodId = $request->id_periode;
        $force = $request->force;

        DB::transaction(function () use ($periodId, $force) {
            $query = BudgetMaster::where('id_periode_anggaran', $periodId);
            
            if (!$force) {
                // Only delete those without usage
                $query->where('anggaran_terikat_ytd', 0)
                      ->where('anggaran_realisasi_ytd', 0);
            }
            
            // Because BudgetDetail relies on BudgetMaster ID, if cascading is not set at DB level,
            // we should delete manually or fetch them and delete.
            // Let's assume cascading is NOT 100% reliable, we'll delete details first.
            $masterIds = $query->pluck('id')->toArray();
            
            if (!empty($masterIds)) {
                BudgetDetail::whereIn('id_budget_master', $masterIds)->delete();
                BudgetMaster::whereIn('id', $masterIds)->delete();
            }
        });

        return redirect()->route('admin.budget.index', ['id_periode' => $periodId])
            ->with('success', 'Data anggaran untuk periode tersebut berhasil direset.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BudgetMaster $budget)
    {
        if ($budget->anggaran_terikat_ytd > 0 || $budget->anggaran_realisasi_ytd > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus anggaran yang sudah digunakan.');
        }

        $budget->delete();

        return redirect()->route('admin.budget.index')->with('success', 'Anggaran berhasil dihapus.');
    }
    /**
     * Get available options based on selection.
     */
    public function getBudgetOptions(Request $request)
    {
        $type = $request->type; // 'program' or 'akun'
        $deptId = $request->id_departemen;

        if (!$deptId) return response()->json([]);
        
        if ($type === 'program') {
            // 1. Programs owned by this Dept
            $ownedProgramIds = ProgramKerja::where('id_departemen', $deptId)->pluck('id')->toArray();

            // 2. Programs from other Depts that have Pos Anggaran delegated to this Dept
            $delegatedProgramIds = \App\Models\PosAnggaran::where('is_active', true)
                ->whereHas('delegasi', function($q) use ($deptId) {
                $q->where('tbl_pos_anggaran_delegasi.id_departemen', $deptId);
            })->pluck('id_program_kerja')->toArray();

            $allProgramIds = array_unique(array_merge($ownedProgramIds, $delegatedProgramIds));

            $programs = ProgramKerja::whereIn('id', $allProgramIds)
                ->with('departemen') // Load dept to show ownership
                ->orderBy('nama_program')
                ->get()
                ->map(function($p) use ($deptId) {
                    return [
                        'id' => $p->id,
                        'nama_program' => '[' . $p->departemen->nama_departemen . '] ' . $p->nama_program
                    ];
                });

            return response()->json($programs);
        }

        if ($type === 'akun') {
            $programId = $request->id_program;
            if (!$programId) return response()->json([]);

            $program = ProgramKerja::find($programId);
            if (!$program) return response()->json([]);

            $isOwned = $program->id_departemen == $deptId;

            $query = \App\Models\PosAnggaran::where('id_program_kerja', $programId)
                ->where('is_active', true)
                ->with('akunGl');

            if (!$isOwned) {
                // If delegated program, ONLY show accounts that are explicitly delegated
                $query->whereHas('delegasi', function($q) use ($deptId) {
                    $q->where('tbl_pos_anggaran_delegasi.id_departemen', $deptId);
                });
            }

            $posAnggaran = $query->get();

            $akuns = $posAnggaran->map(function($pos) {
                return [
                    'id' => $pos->id, // This is now ID POS ANGGARAN
                    'kode_akun' => $pos->akunGl->kode_akun,
                    'nama_akun' => $pos->akunGl->nama_akun,
                    'full_label' => $pos->akunGl->kode_akun . ' - ' . $pos->akunGl->nama_akun
                ];
            });

            return response()->json($akuns);
        }
        
        return response()->json([]);
    }
}
