<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Enums\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class TicketController extends Controller
{
    /**
     * Display a listing of the tickets (Management View).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Authorization: Only IT/Admin can access Management View
        if (!$user->hasRole(Role::itRoles())) {
            abort(403);
        }

        $query = Ticket::with(['reporter', 'assignee']);

        // Filter: Assigned to Me (Tab "Tugas Saya")
        if ($request->tab === 'assigned') {
            $query->where('assigned_to', $user->id)
                  ->whereNotIn('status', ['Resolved', 'Closed']);
        }

        // Search
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('subject', 'like', '%'.$request->search.'%')
                  ->orWhere('category', 'like', '%'.$request->search.'%');
            });
        }

        // Status Filter
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderByRaw("FIELD(priority, 'Critical', 'High', 'Medium', 'Low')")
                         ->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        return Inertia::render('Admin/Ticket/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'status', 'tab']),
            'isIT' => true
        ]);
    }

    /**
     * Display a listing of user's own tickets (Personal View).
     */
    public function myRequests(Request $request)
    {
        $user = Auth::user();
        
        $query = Ticket::where('user_id', $user->id)
                       ->with(['assignee']); // Eager load assignee just for info

        if ($request->search) {
            $query->where('subject', 'like', '%'.$request->search.'%');
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $tickets = $query->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        return Inertia::render('Admin/Ticket/MyIndex', [
            'tickets' => $tickets,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        return Inertia::render('Admin/Ticket/Create');
    }

    /**
     * Store a newly created ticket in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('tickets', 'public');
        }

        Ticket::create([
            'user_id' => Auth::id(),
            'category' => $request->category,
            'subject' => $request->subject,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'Open',
            'attachment_path' => $path,
        ]);

        return redirect()->route('admin.tickets.my-requests')->with('success', 'Tiket berhasil dibuat.');
    }

    /**
     * Display the specified ticket.
     */
    public function show(Ticket $ticket)
    {
        $user = Auth::user();

        // Authorization Check
        if (!$user->hasRole(Role::itRoles()) && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->load(['reporter', 'assignee', 'comments.user']);

        // Get IT Staff list for assignment dropdown (Only for IT)
        $itStaff = [];
        if ($user->hasRole(Role::itRoles())) {
            $itStaff = User::role([Role::IT_SUPPORT->value, Role::SUPER_ADMIN->value])->get(['id', 'name']);
        }

        return Inertia::render('Admin/Ticket/Show', [
            'ticket' => $ticket,
            'itStaff' => $itStaff,
            'isIT' => $user->hasRole(Role::itRoles())
        ]);
    }

    /**
     * Update the specified ticket in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        if (!$user->hasRole(Role::itRoles())) {
             abort(403);
        }

        $request->validate([
            'status' => 'required|string',
            'priority' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update([
            'status' => $request->status,
            'priority' => $request->priority,
            'assigned_to' => $request->assigned_to,
        ]);

        return redirect()->back()->with('success', 'Status tiket diperbarui.');
    }

    /**
     * Store a comment for the ticket.
     */
    public function storeComment(Request $request, Ticket $ticket)
    {
        $user = Auth::user();
        if (!$user->hasRole(Role::itRoles()) && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
             $path = $request->file('attachment')->store('ticket_comments', 'public');
        }

        $ticket->comments()->create([
            'user_id' => Auth::id(),
            'message' => $request->message,
            'attachment_path' => $path,
        ]);

        // Auto-update status to 'In Progress' if IT replies to an OPEN ticket?
        // Optional logic. For now keep simple.

        return redirect()->back()->with('success', 'Komentar terkirim.');
    }

    /**
     * Remove the specified ticket from storage.
     */
    public function destroy(Ticket $ticket)
    {
        if (!Auth::user()->hasRole(Role::SUPER_ADMIN->value)) {
             abort(403);
        }

        // Hapus semua attachment (tiket + semua komentar)
        if ($ticket->attachment_path) {
            Storage::disk('public')->delete($ticket->attachment_path);
        }

        foreach ($ticket->comments as $comment) {
            if ($comment->attachment_path) {
                Storage::disk('public')->delete($comment->attachment_path);
            }
        }

        $ticket->delete();

        return redirect()->route('admin.tickets.index')->with('success', 'Tiket dihapus.');
    }
}
