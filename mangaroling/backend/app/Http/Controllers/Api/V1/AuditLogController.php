<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** v1 is single-organization, so the log is not org-scoped (failed logins have no user). */
class AuditLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'action' => ['sometimes', 'string', 'max:100'],
            'entity_type' => ['sometimes', 'string', 'max:100'],
            'user_id' => ['sometimes', 'integer'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $query = AuditLog::query()->with('user:id,name,email')->orderByDesc('id');

        if (isset($filters['action'])) {
            $query->where('action', 'like', addcslashes($filters['action'], '%_\\').'%');
        }
        if (isset($filters['entity_type'])) $query->where('entity_type', $filters['entity_type']);
        if (isset($filters['user_id'])) $query->where('user_id', $filters['user_id']);
        if (isset($filters['from'])) $query->where('created_at', '>=', $filters['from']);
        if (isset($filters['to'])) $query->where('created_at', '<=', $filters['to']);

        return AuditLogResource::collection($query->paginate(50)->withQueryString());
    }
}
