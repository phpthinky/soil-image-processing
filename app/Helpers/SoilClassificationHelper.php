<?php

namespace App\Helpers;

/**
 * SoilClassificationHelper
 *
 * Classifies soil readings against crop thresholds stored as Low / Medium / High
 * values and computes fertilizer recommendations using the client formula:
 *
 *   Step 1 — Deficit:
 *     deficit = crop_target (med) − current_soil
 *
 *   Step 2 — Fertilizer amount (kg/ha):
 *     fertilizer_amount = deficit / nutrient_fraction
 *
 * Column naming convention on the Crop model:
 *   ph_low / ph_med / ph_high
 *   n_low  / n_med  / n_high
 *   p_low  / p_med  / p_high
 *   k_low  / k_med  / k_high
 *
 * NOTE: CropCategoryHelper is NOT modified — this is a separate helper.
 */
class SoilClassificationHelper
{
    // -----------------------------------------------------------------------
    // CLASSIFICATION
    // -----------------------------------------------------------------------

    /**
     * Classify a soil value against Low / Medium / High thresholds.
     *
     * Logic:
     *   value <= low    → "Low"
     *   value >= high   → "High"
     *   otherwise       → "Medium"
     *
     * @param  float       $value   Measured soil value
     * @param  float|null  $low     Upper bound of Low band
     * @param  float|null  $high    Lower bound of High band
     * @return string  'Low' | 'Medium' | 'High'
     */
    public static function classify(float $value, ?float $low, ?float $high): string
    {
        if ($low !== null && $value <= $low)  return 'Low';
        if ($high !== null && $value >= $high) return 'High';
        return 'Medium';
    }

    /**
     * Classify all four parameters (pH, N, P, K) for a crop at once.
     *
     * @param  \App\Models\Crop  $crop
     * @param  float  $ph
     * @param  float  $n   (ppm)
     * @param  float  $p   (ppm)
     * @param  float  $k   (ppm)
     * @return array{ph:string, n:string, p:string, k:string}
     */
    public static function classifyAll($crop, float $ph, float $n, float $p, float $k): array
    {
        return [
            'ph' => self::classify($ph, $crop->ph_low, $crop->ph_high),
            'n'  => self::classify($n,  $crop->n_low,  $crop->n_high),
            'p'  => self::classify($p,  $crop->p_low,  $crop->p_high),
            'k'  => self::classify($k,  $crop->k_low,  $crop->k_high),
        ];
    }

    // -----------------------------------------------------------------------
    // SCORING
    // -----------------------------------------------------------------------

    /**
     * Score a classification: Medium = 1.0 (full match), Low/High = 0.33.
     */
    public static function score(string $classification): float
    {
        return $classification === 'Medium' ? 1.0 : 0.33;
    }

    /**
     * Overall compatibility percentage from an array of scores (0–100).
     */
    public static function overallScore(array $scores): float
    {
        if (empty($scores)) return 0.0;
        return round(array_sum($scores) / count($scores) * 100, 2);
    }

    // -----------------------------------------------------------------------
    // FERTILIZER FORMULA
    //
    //   deficit          = crop_target (med) − current_soil
    //   fertilizer_kgha  = deficit / nutrient_fraction
    // -----------------------------------------------------------------------

    /**
     * Full fertilizer recommendation for N, P, K.
     *
     * Soil values and crop thresholds are in ppm.
     * Deficit (ppm) is converted to kg/ha before dividing by fertilizer fraction
     * so the result is kg/ha of fertilizer product to apply.
     *
     * Conversion: kg/ha = ppm × 1.3 (bulk density) × 20 cm × 0.1 = ppm × 2.6
     *
     * Default fertilizer fractions:
     *   N → 0.46  (Urea 46-0-0)
     *   P → 0.20  (SSP 0-20-0)
     *   K → 0.60  (MOP 0-0-60)
     *
     * @param  \App\Models\Crop  $crop
     * @param  float  $n  Current soil N (ppm)
     * @param  float  $p  Current soil P (ppm)
     * @param  float  $k  Current soil K (ppm)
     * @return array
     */
    public static function fertilizerRecommendation($crop, float $n, float $p, float $k): array
    {
        $nTarget = (float)($crop->n_med ?? 0);
        $pTarget = (float)($crop->p_med ?? 0);
        $kTarget = (float)($crop->k_med ?? 0);

        // Deficit in ppm
        $nDefPpm = max(0.0, $nTarget - $n);
        $pDefPpm = max(0.0, $pTarget - $p);
        $kDefPpm = max(0.0, $kTarget - $k);

        // Convert deficit ppm → kg/ha for the fertilizer formula
        $nDefKg = self::ppmToKgPerHa($nDefPpm);
        $pDefKg = self::ppmToKgPerHa($pDefPpm);
        $kDefKg = self::ppmToKgPerHa($kDefPpm);

        $nFrac = 0.46;
        $pFrac = 0.20;
        $kFrac = 0.60;

        return [
            'n' => [
                'target'          => round($nTarget, 2),
                'current'         => round($n, 2),
                'deficit_ppm'     => round($nDefPpm, 2),
                'deficit_kgha'    => $nDefKg,
                'fraction'        => $nFrac,
                'fertilizer_kgha' => $nDefKg > 0 ? round($nDefKg / $nFrac, 2) : 0,
            ],
            'p' => [
                'target'          => round($pTarget, 2),
                'current'         => round($p, 2),
                'deficit_ppm'     => round($pDefPpm, 2),
                'deficit_kgha'    => $pDefKg,
                'fraction'        => $pFrac,
                'fertilizer_kgha' => $pDefKg > 0 ? round($pDefKg / $pFrac, 2) : 0,
            ],
            'k' => [
                'target'          => round($kTarget, 2),
                'current'         => round($k, 2),
                'deficit_ppm'     => round($kDefPpm, 2),
                'deficit_kgha'    => $kDefKg,
                'fraction'        => $kFrac,
                'fertilizer_kgha' => $kDefKg > 0 ? round($kDefKg / $kFrac, 2) : 0,
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // PPM → kg/ha CONVERSION
    // -----------------------------------------------------------------------

    /**
     * Convert soil nutrient concentration (ppm) to kg/ha.
     *
     * kg/ha = ppm × bulk_density (g/cm³) × depth (cm) × 0.1
     * Default: 1.3 g/cm³ × 20 cm × 0.1 = factor 2.6
     */
    public static function ppmToKgPerHa(float $ppm, float $bulkDensity = 1.3, float $depthCm = 20.0): float
    {
        return round($ppm * $bulkDensity * $depthCm * 0.1, 2);
    }
}
