<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only: UPDATE/DELETE are rejected by a PostgreSQL trigger. */
class DonationVerificationLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['donation_id', 'verified_by', 'previous_status', 'new_status', 'notes'];

    public function donation(): BelongsTo { return $this->belongsTo(Donation::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
