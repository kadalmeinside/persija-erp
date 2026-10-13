<?php

namespace App\Services;

use App\Models\ApprovalDelegation;
use App\Models\ApprovalProcess;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalGovernanceService
{
    /**
     * Check if a user has an active delegation for today.
     * Returns the delegate user if found, null otherwise.
     */
    public function getActiveDelegate(int $userId): ?User
    {
        $today = now()->toDateString();
        $delegation = ApprovalDelegation::where('delegator_user_id', $userId)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('delegate')
            ->first();

        return $delegation?->delegate;
    }

    /**
     * Reassign a pending approval step to a new target employee/user.
     */
    public function reassign(ApprovalProcess $step, int $newKaryawanId, string $reason, User $actor): ApprovalProcess
    {
        if ($step->status !== 'Pending') {
            throw new \RuntimeException('Hanya langkah approval dengan status Pending yang dapat dialihkan.');
        }

        $targetKaryawan = Karyawan::findOrFail($newKaryawanId);

        return DB::transaction(function () use ($step, $targetKaryawan, $reason, $actor) {
            $prevTargetId = $step->id_karyawan_target;

            $step->update([
                'reassigned_from_user_id' => $actor->id,
                'reassigned_reason' => $reason,
                'id_karyawan_target' => $targetKaryawan->id,
            ]);

            ImmutableAuditService::log(
                action: 'APPROVAL_REASSIGNED',
                entityType: 'ApprovalProcess',
                entityId: (string) $step->id,
                payload: [
                    'step_id' => $step->id,
                    'prev_target_karyawan_id' => $prevTargetId,
                    'new_target_karyawan_id' => $targetKaryawan->id,
                    'reason' => $reason,
                ],
                actorId: $actor->id,
                actorName: $actor->name,
            );

            return $step;
        });
    }

    /**
     * Check and escalate pending approvals exceeding SLA hours (default 24h).
     */
    public function checkAndEscalateOverdue(int $slaHours = 24): int
    {
        $cutoff = now()->subHours($slaHours);

        $overdueSteps = ApprovalProcess::where('status', 'Pending')
            ->whereNull('sla_escalated_at')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $escalatedCount = 0;

        foreach ($overdueSteps as $step) {
            DB::transaction(function () use ($step) {
                $step->update([
                    'sla_escalated_at' => now(),
                    'sla_escalation_count' => ($step->sla_escalation_count ?? 0) + 1,
                ]);

                ImmutableAuditService::log(
                    action: 'APPROVAL_SLA_ESCALATED',
                    entityType: 'ApprovalProcess',
                    entityId: (string) $step->id,
                    payload: [
                        'step_id' => $step->id,
                        'level_order' => $step->level_order,
                        'pending_since' => $step->created_at->toIso8601String(),
                        'escalated_at' => now()->toIso8601String(),
                    ],
                    actorId: null,
                    actorName: 'System SLA Monitor',
                );
            });

            $escalatedCount++;
        }

        return $escalatedCount;
    }
}
