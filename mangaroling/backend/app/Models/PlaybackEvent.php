<?php

namespace App\Models;

use App\Enums\PlaybackEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['invitation_id', 'song_id', 'event_type'];

    protected function casts(): array { return ['event_type' => PlaybackEventType::class]; }

    public function invitation(): BelongsTo { return $this->belongsTo(Invitation::class); }
    public function song(): BelongsTo { return $this->belongsTo(Song::class); }
}
