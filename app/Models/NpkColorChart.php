<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpkColorChart extends Model
{
    protected $fillable = ['nutrient', 'ppm_value', 'hex_value', 'category', 'active'];

    protected $casts = [
        'ppm_value' => 'float',
        'active'    => 'boolean',
    ];

    /**
     * Returns active color chart as [hex_value => ppm_value] for a given nutrient.
     * Used by ColorScienceService for CIEDE2000 delta-E matching.
     */
    public static function chartForNutrient(string $nutrient): array
    {
        return static::where('nutrient', strtoupper($nutrient))
            ->where('active', true)
            ->orderBy('ppm_value')
            ->get()
            ->mapWithKeys(fn($row) => [$row->hex_value => $row->ppm_value])
            ->toArray();
    }
}
