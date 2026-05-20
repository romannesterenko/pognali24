<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TripBooking::class);
    }

    public function fromLocality(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class,
            'from_fias_id',
            'fias_id'
        );
    }

    public function toLocality(): BelongsTo
    {
        return $this->belongsTo(
            Locality::class,
            'to_fias_id',
            'fias_id'
        );    }
}
