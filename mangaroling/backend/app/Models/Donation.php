<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\PaymentProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donation extends Model
{
    use HasFactory;

    protected $fillable = ['invitation_id', 'payment_account_id', 'payment_method', 'payment_account_snapshot',
        'amount', 'currency', 'reference_number', 'proof_path', 'idempotency_key', 'status',
        'submitted_at', 'verified_at', 'verified_by'];

    protected $hidden = ['proof_path'];

    protected function casts(): array
    {
        return ['status' => DonationStatus::class, 'payment_method' => PaymentProvider::class,
            'payment_account_snapshot' => 'array', 'amount' => 'decimal:2',
            'submitted_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function invitation(): BelongsTo { return $this->belongsTo(Invitation::class); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function verificationLogs(): HasMany { return $this->hasMany(DonationVerificationLog::class); }

    /** BR-010: the only scope financial totals may use. */
    public function scopeOfficial(Builder $q): Builder { return $q->where('status', DonationStatus::Verified->value); }
}
