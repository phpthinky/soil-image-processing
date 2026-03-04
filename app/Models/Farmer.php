<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farmer extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'address',
        'farm_location',
        'farm_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function soilSamples(): HasMany
    {
        return $this->hasMany(SoilSample::class);
    }
}
