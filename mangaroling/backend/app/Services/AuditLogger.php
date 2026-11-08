<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * FR-012: single entry point for audit records. Never pass passwords, tokens
 * or other secrets in $details.
 */
class AuditLogger
{
    public function record(
        string $action,
        string $entityType,
        ?int $entityId = null,
        array $details = [],
        ?User $actor = null,
    ): AuditLog {
        $request = request();
        $actor ??= $request->user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details + [
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255, ''),
            ],
        ]);
    }
}
