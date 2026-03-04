<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoilColorReading extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sample_id', 'parameter', 'test_number',
        'color_hex', 'r', 'g', 'b', 'computed_value', 'captured_at',
    ];

    public function sample(): BelongsTo
    {
        return $this->belongsTo(SoilSample::class, 'sample_id');
    }
}
