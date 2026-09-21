<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('causer')
            ->latest()
            ->when($request->search, function ($query, $search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });

        // Filter by subject type if needed (optional for future)
        if ($request->subject_type) {
            $query->where('subject_type', $request->subject_type);
        }

        $activities = $query->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Activity/Index', [
            'activities' => $activities,
            'filters' => $request->only(['search', 'subject_type']),
        ]);
    }
}
