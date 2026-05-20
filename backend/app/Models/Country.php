<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $guarded = [];

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function localities(): HasMany
    {
        return $this->hasMany(Locality::class);
    }
}
