<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TripBooking::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
