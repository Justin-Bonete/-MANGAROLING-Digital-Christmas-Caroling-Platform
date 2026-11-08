<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'manager_id', 'title', 'description', 'theme', 'status',
        'donations_enabled', 'donation_message', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['status' => CampaignStatus::class, 'donations_enabled' => 'boolean',
            'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'manager_id'); }
    public function campaignRecipients(): HasMany { return $this->hasMany(CampaignRecipient::class); }
    public function recipients(): BelongsToMany { return $this->belongsToMany(Recipient::class, 'campaign_recipients'); }
    public function songs(): BelongsToMany { return $this->belongsToMany(Song::class, 'campaign_songs'); }
    public function paymentAccounts(): HasMany { return $this->hasMany(PaymentAccount::class); }

    /** Songs that may legally and actively be played (BR-006). */
    public function eligibleSongs(): BelongsToMany
    {
        return $this->songs()->where('songs.is_active', true)
            ->whereIn('songs.license_status', \App\Enums\SongLicenseStatus::eligible());
    }
}
