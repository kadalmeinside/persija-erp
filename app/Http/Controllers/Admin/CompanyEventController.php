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
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : $startDate->copy()->addHour();
        if ($endDate->lte($startDate)) {
            $endDate = $startDate->copy()->addHour();
        }

        // Check for room/location conflicts
        $this->validateRoomAvailability($request->location, $startDate, $endDate);

        CompanyEvent::create([
            'title' => $request->title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $request->description,
            'location' => $request->location ? trim($request->location) : null,
            'color' => 'purple', // Default color
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
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : $startDate->copy()->addHour();
        if ($endDate->lte($startDate)) {
            $endDate = $startDate->copy()->addHour();
        }

        // Check for room/location conflicts ignoring current event
        $this->validateRoomAvailability($request->location, $startDate, $endDate, $companyEvent->id);

        $companyEvent->update([
            'title' => $request->title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $request->description,
            'location' => $request->location ? trim($request->location) : null,
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
     */
    protected function validateRoomAvailability(?string $location, \Carbon\Carbon $start, \Carbon\Carbon $end, ?int $ignoreId = null): void
    {
        $normalizedLocation = trim($location ?? '');
        if (empty($normalizedLocation)) {
            return;
        }

        // Check for time overlap in the same room/location
        $query = CompanyEvent::whereNotNull('location')
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
                'location' => "Bentrok Jadwal! Lokasi/Ruangan '{$conflict->location}' sudah terpakai oleh agenda '{$conflict->title}' pada {$conflictStart} s/d {$conflictEnd}. Silakan gunakan ruangan lain atau ubah jam pelaksanaan.",
            ]);
        }
    }
}
