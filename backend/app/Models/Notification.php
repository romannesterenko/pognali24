<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{

    protected $guarded = [];
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
