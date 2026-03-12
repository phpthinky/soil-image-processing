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
     * Group 1 — Tolerance Match
     * pH must be within range; ranked by how many of all 4 parameters match.
     * "Can plant with the current soil as-is."
     */
    public static function groupByTolerance(float $ph, float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS match_score", [$ph, $n, $p, $k])
            ->whereRaw('? BETWEEN min_ph AND max_ph', [$ph])
            ->orderByDesc('match_score')
            ->orderBy('name')
            ->get();
    }

    /**
     * Group 2 — Fertility Score
     * Ranked by NPK compatibility only; no pH filter.
     * Addresses crops that match the soil's nutrient profile but whose pH requirement
     * differs slightly — a lime or sulfur amendment can correct the pH.
     */
    public static function groupByFertility(float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS npk_score", [$n, $p, $k])
            ->havingRaw('npk_score > 0')
            ->orderByDesc('npk_score')
            ->orderBy('name')
            ->get();
    }

    /**
     * Group 3 — pH Threshold
     * All crops whose pH tolerance covers the current soil pH, ranked by NPK score.
     * Shows every species that can survive this soil's acidity/alkalinity level.
     */
    public static function groupByPh(float $ph, float $n, float $p, float $k)
    {
        return static::selectRaw("*, (
            CASE WHEN ? BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
            CASE WHEN ? BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END
        ) AS npk_score", [$n, $p, $k])
            ->whereRaw('? BETWEEN min_ph AND max_ph', [$ph])
            ->orderByDesc('npk_score')
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
