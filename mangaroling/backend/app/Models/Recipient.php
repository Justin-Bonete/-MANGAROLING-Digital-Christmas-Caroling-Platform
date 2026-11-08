<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipient extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'name', 'email', 'nickname', 'relationship'];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function campaignRecipients(): HasMany { return $this->hasMany(CampaignRecipient::class); }
}
