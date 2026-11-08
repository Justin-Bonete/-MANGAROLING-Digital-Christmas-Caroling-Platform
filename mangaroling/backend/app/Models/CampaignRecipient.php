<?php

namespace App\Models;

use App\Enums\ConsentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignRecipient extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['campaign_id', 'recipient_id', 'group_label', 'consent_status',
        'consented_at', 'opted_out_at', 'contact_preferences'];

    protected function casts(): array
    {
        return ['consent_status' => ConsentStatus::class, 'consented_at' => 'datetime',
            'opted_out_at' => 'datetime', 'contact_preferences' => 'array'];
    }

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function recipient(): BelongsTo { return $this->belongsTo(Recipient::class); }
    public function invitations(): HasMany { return $this->hasMany(Invitation::class); }

    public function canBeEmailed(): bool
    {
        return $this->consent_status === ConsentStatus::Granted && $this->opted_out_at === null;
    }
}
