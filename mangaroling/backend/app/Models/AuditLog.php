<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only: UPDATE/DELETE are rejected by a PostgreSQL trigger. */
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'details'];
    protected function casts(): array { return ['details' => 'array']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
