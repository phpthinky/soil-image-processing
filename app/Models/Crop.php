<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Crop extends Model
{
    protected $fillable = [
        'name', 'description',
        'min_ph', 'max_ph',
        'min_nitrogen', 'max_nitrogen',
        'min_phosphorus', 'max_phosphorus',
        'min_potassium', 'max_potassium',
    ];

    /**
     * Return all crops with a match score for the given soil readings,
     * filtered to those where pH is within range, ordered by score desc.
     */
    public static function matchingForSoil(float $ph, float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS match_score", [$ph, $n, $p, $k])
            ->whereRaw('min_ph <= ? AND max_ph >= ?', [$ph, $ph])
            ->orderByDesc('match_score')
            ->orderBy('name')
            ->get();
    }

    /**
     * Return the top-matching crop name for the given soil readings.
     */
    public static function topMatchName(float $ph, float $n, float $p, float $k): ?string
    {
        $crop = static::selectRaw("name, (
            CASE WHEN ? BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS score", [$ph, $n, $p, $k])
            ->orderByDesc('score')
            ->orderBy('name')
            ->first();

        return $crop?->name;
    }
}
