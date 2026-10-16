<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyEvent;
use Illuminate\Support\Facades\Auth;

class CompanyEventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CompanyEvent::query();

        if ($request->search) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $events = $query->orderBy('start_date', 'desc')
                        ->paginate(10)
                        ->withQueryString();

        return \Inertia\Inertia::render('Admin/CompanyEvent/Index', [
            'events' => $events,
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|in:meeting,general,training,online,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $category = $request->input('category', 'meeting') ?: 'meeting';
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : $startDate->copy()->addHour();
        if ($endDate->lte($startDate)) {
            $endDate = $startDate->copy()->addHour();
        }

        // Color coding based on category
        $colorMap = [
            'meeting' => 'purple',
            'general' => 'emerald',
            'training' => 'amber',
            'online' => 'sky',
            'other' => 'indigo',
        ];
        $color = $colorMap[$category] ?? 'purple';

        // Check for room/location conflicts ONLY IF category is 'meeting'
        $this->validateRoomAvailability($category, $request->location, $startDate, $endDate);

        CompanyEvent::create([
            'title' => $request->title,
            'category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $request->description,
            'location' => $request->location ? trim($request->location) : null,
            'color' => $color,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Agenda perusahaan berhasil dibuat.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CompanyEvent $companyEvent)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|in:meeting,general,training,online,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $category = $request->input('category', $companyEvent->category ?? 'meeting') ?: 'meeting';
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : $startDate->copy()->addHour();
        if ($endDate->lte($startDate)) {
            $endDate = $startDate->copy()->addHour();
        }

        $colorMap = [
            'meeting' => 'purple',
            'general' => 'emerald',
            'training' => 'amber',
            'online' => 'sky',
            'other' => 'indigo',
        ];
        $color = $colorMap[$category] ?? 'purple';

        // Check for room/location conflicts ignoring current event
        $this->validateRoomAvailability($category, $request->location, $startDate, $endDate, $companyEvent->id);

        $companyEvent->update([
            'title' => $request->title,
            'category' => $category,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $request->description,
            'location' => $request->location ? trim($request->location) : null,
            'color' => $color,
        ]);

        return redirect()->back()->with('success', 'Agenda perusahaan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyEvent $companyEvent)
    {
        $companyEvent->delete();
        return redirect()->back()->with('success', 'Agenda perusahaan berhasil dihapus.');
    }

    /**
     * Validate whether the selected room/location is available during the time window.
     * Overlap prevention ONLY triggers if category is 'meeting' (or meeting room booking)
     * and location is filled. Other types (general events, trainings, online) ARE ALLOWED TO OVERLAP.
     */
    protected function validateRoomAvailability(string $category, ?string $location, \Carbon\Carbon $start, \Carbon\Carbon $end, ?int $ignoreId = null): void
    {
        // 1. Yang lain boleh bentrok! Hanya jenis 'meeting' yang divalidasi bentrok ruangan.
        if ($category !== 'meeting') {
            return;
        }

        $normalizedLocation = trim($location ?? '');
        if (empty($normalizedLocation)) {
            return;
        }

        // Check for time overlap in the same room/location among meetings
        $query = CompanyEvent::where(function ($q) {
                $q->where('category', 'meeting')
                  ->orWhereNull('category');
            })
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->whereRaw('LOWER(TRIM(location)) = ?', [strtolower($normalizedLocation)])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<', $end)
                  ->where(function ($sub) use ($start) {
                      $sub->where('end_date', '>', $start)
                          ->orWhere(function ($fallback) use ($start) {
                              $fallback->whereNull('end_date')
                                       ->where('start_date', '>=', $start);
                          });
                  });
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $conflict = $query->first();

        if ($conflict) {
            $conflictStart = $conflict->start_date->format('d/m/Y H:i');
            $conflictEnd = $conflict->end_date ? $conflict->end_date->format('d/m/Y H:i') : $conflictStart;

            throw \Illuminate\Validation\ValidationException::withMessages([
                'location' => "Bentrok Ruang Rapat! Ruangan '{$conflict->location}' sudah digunakan untuk meeting '{$conflict->title}' pada jam {$conflictStart} s/d {$conflictEnd}. Silakan gunakan ruangan lain atau ubah jam rapat.",
            ]);
        }
    }
}
