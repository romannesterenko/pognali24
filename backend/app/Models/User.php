<?php

namespace App\Models;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// use Illuminate\Contracts\Auth\MustVerifyEmail;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;

    protected $guarded = [];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function driverProfile(): HasOne
    {
        return $this->hasOne(DriverProfile::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TripBooking::class, 'passenger_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ConversationMember::class);
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'reviewee_id'
        );
    }

    public function givenReviews(): HasMany
    {
        return $this->hasMany(
            Review::class,
            'reviewer_id'
        );
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
