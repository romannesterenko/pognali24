<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $guarded = [];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
