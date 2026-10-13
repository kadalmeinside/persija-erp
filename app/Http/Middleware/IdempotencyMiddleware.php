<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\IdempotencyKey;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request with Idempotency Key protection.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('X-Idempotency-Key') ?? $request->header('Idempotency-Key');

        // Only enforce when header is present and request is a mutation
        if (!$idempotencyKey || !in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        $userId = $request->user()?->id;
        $requestHash = hash('sha256', json_encode($request->all()));
        $endpoint = $request->path();

        $existing = IdempotencyKey::where('key', $idempotencyKey)
            ->where(function ($q) use ($userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            })
            ->first();

        if ($existing) {
            if ($existing->status === 'completed') {
                $cachedBody = json_decode($existing->response_body, true) ?? $existing->response_body;
                return response()->json($cachedBody, $existing->response_code, [
                    'X-Idempotent-Replay' => 'true',
                ]);
            }

            if ($existing->status === 'processing') {
                // If locked within last 60 seconds, prevent duplicate execution
                if ($existing->locked_at && $existing->locked_at->gt(now()->subSeconds(60))) {
                    return response()->json([
                        'message' => 'Permintaan sedang diproses. Mohon tunggu beberapa saat.',
                    ], 409);
                }
            }
        }

        $record = IdempotencyKey::updateOrCreate(
            ['key' => $idempotencyKey],
            [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'request_hash' => $requestHash,
                'status' => 'processing',
                'locked_at' => now(),
            ]
        );

        try {
            $response = $next($request);

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $content = $response->getContent();
                $record->update([
                    'status' => 'completed',
                    'response_code' => $response->getStatusCode(),
                    'response_body' => $content,
                ]);
            } else {
                $record->update(['status' => 'failed']);
            }

            return $response;
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed']);
            throw $e;
        }
    }
}
