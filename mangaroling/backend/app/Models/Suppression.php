<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suppression extends Model
{
    const UPDATED_AT = null;
    protected $fillable = ['organization_id', 'email', 'reason'];
}
