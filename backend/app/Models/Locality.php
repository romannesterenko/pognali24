<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Locality extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function ($locality) {

            if (!$locality->slug) {

                $locality->slug = Str::slug($locality->name);

            }

        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
