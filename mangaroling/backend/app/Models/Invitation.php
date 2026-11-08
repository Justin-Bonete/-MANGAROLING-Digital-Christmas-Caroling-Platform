<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    use HasFactory;

    protected $fillable = ['campaign_recipient_id', 'token_hash', 'selected_song_id', 'selected_at', 'status',
        'expires_at', 'sent_at', 'delivered_at', 'first_opened_at', 'revoked_at', 'revoked_by'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['status' => InvitationStatus::class, 'selected_at' => 'datetime', 'expires_at' => 'datetime',
            'sent_at' => 'datetime', 'delivered_at' => 'datetime', 'first_opened_at' => 'datetime',
            'revoked_at' => 'datetime'];
    }

    public static function hashToken(string $rawToken): string { return hash('sha256', $rawToken); }

    public function campaignRecipient(): BelongsTo { return $this->belongsTo(CampaignRecipient::class); }
    public function selectedSong(): BelongsTo { return $this->belongsTo(Song::class, 'selected_song_id'); }
    public function playbackEvents(): HasMany { return $this->hasMany(PlaybackEvent::class); }
    public function donations(): HasMany { return $this->hasMany(Donation::class); }
    public function emailLogs(): HasMany { return $this->hasMany(EmailLog::class); }

    /** BR-004: expired/revoked invitations never grant access. */
    public function grantsAccess(): bool
    {
        return $this->revoked_at === null
            && ! in_array($this->status, [InvitationStatus::Revoked, InvitationStatus::Expired, InvitationStatus::Draft], true)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
