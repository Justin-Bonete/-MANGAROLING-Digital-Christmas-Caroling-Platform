<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['campaign_id', 'invitation_id', 'recipient_email', 'provider_message_id', 'status', 'error', 'sent_at'];
    protected function casts(): array { return ['sent_at' => 'datetime']; }

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function invitation(): BelongsTo { return $this->belongsTo(Invitation::class); }
}
