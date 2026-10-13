<?php

namespace App\Services;

use App\Models\ImmutableAuditLog;
use Illuminate\Support\Facades\DB;

class ImmutableAuditService
{
    const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Record an immutable, tamper-evident audit event with cryptographic hash chaining.
     */
    public static function log(
        string $action,
        string $entityType,
        string $entityId,
        array $payload,
        ?int $actorId = null,
        ?string $actorName = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ImmutableAuditLog {
        return DB::transaction(function () use (
            $action,
            $entityType,
            $entityId,
            $payload,
            $actorId,
            $actorName,
            $ipAddress,
            $userAgent
        ) {
            $last = ImmutableAuditLog::lockForUpdate()->orderBy('sequence_number', 'desc')->first();

            $sequence = $last ? ($last->sequence_number + 1) : 1;
            $prevHash = $last ? $last->current_hash : self::GENESIS_HASH;

            $payloadJson = json_encode($payload);
            $hashInput = implode('|', [
                $sequence,
                $prevHash,
                $action,
                $entityType,
                $entityId,
                $actorId ?? 'system',
                $payloadJson,
            ]);

            $currentHash = hash('sha256', $hashInput);

            return ImmutableAuditLog::create([
                'sequence_number' => $sequence,
                'prev_hash' => $prevHash,
                'current_hash' => $currentHash,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'action' => $action,
                'actor_id' => $actorId,
                'actor_name' => $actorName ?? 'System',
                'payload' => $payload,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Verify the integrity of the audit chain.
     */
    public static function verifyChain(): array
    {
        $logs = ImmutableAuditLog::orderBy('sequence_number', 'asc')->get();
        $expectedPrevHash = self::GENESIS_HASH;

        foreach ($logs as $log) {
            if ($log->prev_hash !== $expectedPrevHash) {
                return [
                    'valid' => false,
                    'broken_at_sequence' => $log->sequence_number,
                    'error' => 'Prev hash mismatch',
                ];
            }

            $payloadJson = json_encode($log->payload);
            $hashInput = implode('|', [
                $log->sequence_number,
                $log->prev_hash,
                $log->action,
                $log->entity_type,
                $log->entity_id,
                $log->actor_id ?? 'system',
                $payloadJson,
            ]);

            $recalculated = hash('sha256', $hashInput);
            if ($recalculated !== $log->current_hash) {
                return [
                    'valid' => false,
                    'broken_at_sequence' => $log->sequence_number,
                    'error' => 'Current hash tampered',
                ];
            }

            $expectedPrevHash = $log->current_hash;
        }

        return [
            'valid' => true,
            'total_verified' => $logs->count(),
        ];
    }
}
