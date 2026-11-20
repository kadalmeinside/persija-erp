<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalDelegation;
use App\Models\ImmutableAuditLog;
use App\Models\ReconciliationLog;
use App\Services\FinancialReconciliationService;
use App\Services\ImmutableAuditService;
use Illuminate\Http\Request;

class GovernanceController extends Controller
{
    public function auditLogs()
    {
        return response()->json([
            'integrity' => ImmutableAuditService::verifyChain(),
            'logs' => ImmutableAuditLog::latest('sequence_number')->paginate(50),
        ]);
    }

    public function reconcile(Request $request, FinancialReconciliationService $service)
    {
        $validated = $request->validate([
            'period' => 'required|date_format:Y-m',
            'module' => 'required|in:Pengajuan,Payroll',
        ]);

        $log = $validated['module'] === 'Pengajuan'
            ? $service->reconcilePengajuan($validated['period'], auth()->id())
            : $service->reconcilePayroll($validated['period'], auth()->id());

        return response()->json([
            'status' => 'success',
            'data' => $log,
            'history' => ReconciliationLog::latest()->take(20)->get(),
        ]);
    }

    public function storeDelegation(Request $request)
    {
        $validated = $request->validate([
            'delegator_user_id' => 'required|exists:users,id',
            'delegate_user_id' => 'required|exists:users,id|different:delegator_user_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $delegation = ApprovalDelegation::create(array_merge($validated, ['is_active' => true]));

        ImmutableAuditService::log(
            action: 'APPROVAL_DELEGATION_CREATED',
            entityType: 'ApprovalDelegation',
            entityId: (string) $delegation->id,
            payload: $validated,
            actorId: auth()->id(),
            actorName: auth()->user()?->name
        );

        return back()->with('success', 'Delegasi approval berhasil disimpan.');
    }
}
