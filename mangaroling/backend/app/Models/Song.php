<?php

namespace App\Models;

use App\Enums\SongLicenseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Song extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'artist', 'category', 'file_path', 'duration_seconds', 'license_status', 'is_active'];

    protected function casts(): array
    {
        return ['license_status' => SongLicenseStatus::class, 'is_active' => 'boolean'];
    }

    public function campaigns(): BelongsToMany { return $this->belongsToMany(Campaign::class, 'campaign_songs'); }

    public function isEligible(): bool
    {
        return $this->is_active && in_array($this->license_status->value, SongLicenseStatus::eligible(), true);
    }
}
