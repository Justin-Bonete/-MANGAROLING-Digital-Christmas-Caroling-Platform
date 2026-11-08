<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAccount extends Model
{
    use HasFactory;

    protected $fillable = ['campaign_id', 'provider', 'account_name', 'account_reference', 'instructions', 'is_active'];

    protected function casts(): array { return ['provider' => PaymentProvider::class, 'is_active' => 'boolean']; }

    public function campaign(): BelongsTo { return $this->belongsTo(Campaign::class); }
    public function donations(): HasMany { return $this->hasMany(Donation::class); }

    /** Frozen copy stored on each donation (BR-015). */
    public function snapshot(): array
    {
        return ['provider' => $this->provider->value, 'account_name' => $this->account_name,
            'account_reference' => $this->account_reference, 'instructions' => $this->instructions];
    }
}
