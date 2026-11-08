<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'status'];

    public function campaigns(): HasMany { return $this->hasMany(Campaign::class); }
    public function recipients(): HasMany { return $this->hasMany(Recipient::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
}
