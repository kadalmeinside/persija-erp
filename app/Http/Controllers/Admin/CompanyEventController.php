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
            'location' => 'nullable|string',
        ]);

        CompanyEvent::create([
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date ?? $request->start_date, // Default to same day if null
            'description' => $request->description,
            'location' => $request->location,
            'color' => 'purple', // Default color
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Event berhasil dibuat.');
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
            'location' => 'nullable|string',
        ]);

        $companyEvent->update([
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date ?? $request->start_date,
            'description' => $request->description,
            'location' => $request->location,
        ]);

        return redirect()->back()->with('success', 'Event berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompanyEvent $companyEvent)
    {
        $companyEvent->delete();
        return redirect()->back()->with('success', 'Event berhasil dihapus.');
    }
}
