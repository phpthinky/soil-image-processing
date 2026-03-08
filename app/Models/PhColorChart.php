<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhColorChart extends Model
{
    protected $fillable = ['indicator', 'ph_value', 'hex_value', 'active'];

    protected $casts = [
        'ph_value' => 'float',
        'active'   => 'boolean',
    ];

    /**
     * Returns active color chart as [hex_value => ph_value] for a given indicator.
     * Used by ColorScienceService for CIEDE2000 delta-E matching.
     */
    public static function chartForIndicator(string $indicator): array
    {
        return static::where('indicator', strtoupper($indicator))
            ->where('active', true)
            ->orderBy('ph_value')
            ->get()
            ->mapWithKeys(fn($row) => [$row->hex_value => $row->ph_value])
            ->toArray();
    }

    /**
     * Returns sorted unique active pH values for a given indicator.
     * Used by PhTestService for snapping computed pH to nearest card point.
     */
    public static function chartPointsForIndicator(string $indicator): array
    {
        return static::where('indicator', strtoupper($indicator))
            ->where('active', true)
            ->distinct()
            ->orderBy('ph_value')
            ->pluck('ph_value')
            ->map(fn($v) => (float) $v)
            ->unique()
            ->values()
            ->toArray();
    }
}
