<?php

namespace App\Helpers;

/**
 * SoilClassificationHelper
 *
 * Uses the Low / Medium / High threshold columns stored on each Crop record
 * (set by technicians) to classify soil readings and compute fertilizer
 * recommendations using the client-specified formula:
 *
 *   Step 1 — Deficit calculation:
 *     crop_target − deficit = current_soil
 *     ∴  deficit = crop_target − current_soil
 *
 *   Step 2 — Fertilizer amount (kg/ha):
 *     fertilizer_amount = deficit / nutrient_fraction
 *
 * Where nutrient_fraction is the proportion of the nutrient in the fertilizer
 * product (e.g. 0.46 for Urea 46-0-0, 0.20 for SSP, 0.60 for MOP).
 *
 * NOTE: Do NOT edit CropCategoryHelper — this is the new standalone helper.
 */
class SoilClassificationHelper
{
    // -----------------------------------------------------------------------
    // CLASSIFICATION
    // -----------------------------------------------------------------------

    /**
     * Classify a soil reading against a crop's Low / Medium / High thresholds.
     *
     * Returns 'Low', 'Medium', or 'High'.
     * Falls back gracefully to the legacy min/max range when the new threshold
     * columns have not yet been filled in by a technician.
     *
     * @param  float       $soilValue     Measured soil value (kg/ha or pH units)
     * @param  float|null  $lowMax        Upper boundary of the Low band
     * @param  float|null  $mediumMin     Lower boundary of the Medium (optimal) band
     * @param  float|null  $mediumMax     Upper boundary of the Medium (optimal) band
     * @param  float|null  $highMin       Lower boundary of the High band
     * @param  float|null  $fallbackMin   Legacy min (used when thresholds are null)
     * @param  float|null  $fallbackMax   Legacy max (used when thresholds are null)
     * @return string  'Low' | 'Medium' | 'High'
     */
    public static function classify(
        float  $soilValue,
        ?float $lowMax,
        ?float $mediumMin,
        ?float $mediumMax,
        ?float $highMin,
        ?float $fallbackMin = null,
        ?float $fallbackMax = null
    ): string {
        // If technician thresholds are set, use them
        if ($lowMax !== null && $mediumMin !== null && $mediumMax !== null && $highMin !== null) {
            if ($soilValue <= $lowMax) {
                return 'Low';
            }
            if ($soilValue >= $highMin) {
                return 'High';
            }
            // Between lowMax and highMin = Medium/optimal
            return 'Medium';
        }

        // Fallback to legacy min/max range
        if ($fallbackMin !== null && $fallbackMax !== null) {
            if ($soilValue < $fallbackMin) return 'Low';
            if ($soilValue > $fallbackMax) return 'High';
            return 'Medium';
        }

        return 'Medium'; // default when no data available
    }

    /**
     * Convenience wrapper: classify all four parameters from a Crop model object.
     *
     * @param  \App\Models\Crop  $crop
     * @param  float  $ph
     * @param  float  $n   Nitrogen   (kg/ha)
     * @param  float  $p   Phosphorus (kg/ha)
     * @param  float  $k   Potassium  (kg/ha)
     * @return array{ph:string, n:string, p:string, k:string}
     */
    public static function classifyAll($crop, float $ph, float $n, float $p, float $k): array
    {
        return [
            'ph' => self::classify(
                $ph,
                $crop->ph_low_max,   $crop->ph_medium_min, $crop->ph_medium_max, $crop->ph_high_min,
                $crop->min_ph,       $crop->max_ph
            ),
            'n'  => self::classify(
                $n,
                $crop->n_low_max,    $crop->n_medium_min,  $crop->n_medium_max,  $crop->n_high_min,
                $crop->min_nitrogen, $crop->max_nitrogen
            ),
            'p'  => self::classify(
                $p,
                $crop->p_low_max,    $crop->p_medium_min,  $crop->p_medium_max,  $crop->p_high_min,
                $crop->min_phosphorus, $crop->max_phosphorus
            ),
            'k'  => self::classify(
                $k,
                $crop->k_low_max,    $crop->k_medium_min,  $crop->k_medium_max,  $crop->k_high_min,
                $crop->min_potassium,  $crop->max_potassium
            ),
        ];
    }

    // -----------------------------------------------------------------------
    // SCORING
    // -----------------------------------------------------------------------

    /**
     * Score a single classification result.
     *
     * Medium (optimal)  → 1.00  (full score)
     * Low or High       → 0.33  (outside optimal range)
     *
     * @param  string  $classification  'Low' | 'Medium' | 'High'
     * @return float
     */
    public static function score(string $classification): float
    {
        return $classification === 'Medium' ? 1.0 : 0.33;
    }

    /**
     * Compute the overall compatibility percentage from an array of scores.
     *
     * @param  float[]  $scores
     * @return float  0–100, rounded to 2 decimal places
     */
    public static function overallScore(array $scores): float
    {
        if (empty($scores)) return 0.0;
        return round(array_sum($scores) / count($scores) * 100, 2);
    }

    // -----------------------------------------------------------------------
    // FERTILIZER FORMULA  (client-specified)
    //
    //   crop_target − deficit = current_soil
    //   ∴ deficit = crop_target − current_soil
    //   fertilizer_amount (kg/ha) = deficit / nutrient_fraction
    // -----------------------------------------------------------------------

    /**
     * Get the crop's target value for a nutrient (midpoint of Medium band, kg/ha).
     *
     * Falls back to the midpoint of min/max range when thresholds are not set.
     *
     * @param  float|null  $mediumMin
     * @param  float|null  $mediumMax
     * @param  float|null  $fallbackMin
     * @param  float|null  $fallbackMax
     * @return float
     */
    public static function cropTarget(
        ?float $mediumMin,
        ?float $mediumMax,
        ?float $fallbackMin = null,
        ?float $fallbackMax = null
    ): float {
        if ($mediumMin !== null && $mediumMax !== null) {
            return ($mediumMin + $mediumMax) / 2.0;
        }
        if ($fallbackMin !== null && $fallbackMax !== null) {
            return ($fallbackMin + $fallbackMax) / 2.0;
        }
        return 0.0;
    }

    /**
     * Calculate the deficit (how much the soil is short of the crop target).
     *
     * A negative deficit means the soil already exceeds the target (surplus).
     * Returns 0 when soil already meets or exceeds the target.
     *
     * @param  float  $cropTarget    Target nutrient level (kg/ha)
     * @param  float  $currentSoil  Measured soil nutrient level (kg/ha)
     * @return float  deficit ≥ 0 (clamped; surpluses returned as 0)
     */
    public static function deficit(float $cropTarget, float $currentSoil): float
    {
        return max(0.0, $cropTarget - $currentSoil);
    }

    /**
     * Calculate the fertilizer amount required (kg/ha) given the deficit and
     * the nutrient fraction of the fertilizer product.
     *
     * fertilizer_amount = deficit / nutrient_fraction
     *
     * @param  float  $deficit           Nutrient deficit (kg/ha)
     * @param  float  $nutrientFraction  Fraction of nutrient in fertilizer (0–1)
     * @return float  kg/ha of fertilizer product to apply
     */
    public static function fertilizerAmount(float $deficit, float $nutrientFraction): float
    {
        if ($nutrientFraction <= 0) return 0.0;
        return round($deficit / $nutrientFraction, 2);
    }

    /**
     * Full fertilizer recommendation for all three nutrients of one crop.
     *
     * Returns an associative array with keys: n, p, k
     * Each key holds: target, current, deficit, fraction, fertilizer_kgha
     *
     * @param  \App\Models\Crop  $crop
     * @param  float  $n  Current soil nitrogen   (kg/ha)
     * @param  float  $p  Current soil phosphorus (kg/ha)
     * @param  float  $k  Current soil potassium  (kg/ha)
     * @return array
     */
    public static function fertilizerRecommendation($crop, float $n, float $p, float $k): array
    {
        $nTarget = self::cropTarget($crop->n_medium_min, $crop->n_medium_max, $crop->min_nitrogen, $crop->max_nitrogen);
        $pTarget = self::cropTarget($crop->p_medium_min, $crop->p_medium_max, $crop->min_phosphorus, $crop->max_phosphorus);
        $kTarget = self::cropTarget($crop->k_medium_min, $crop->k_medium_max, $crop->min_potassium, $crop->max_potassium);

        $nDeficit = self::deficit($nTarget, $n);
        $pDeficit = self::deficit($pTarget, $p);
        $kDeficit = self::deficit($kTarget, $k);

        // Default fertilizer fractions when not set by technician
        $nFraction = $crop->n_fertilizer_fraction ?? 0.46; // Urea 46-0-0
        $pFraction = $crop->p_fertilizer_fraction ?? 0.20; // SSP  0-20-0
        $kFraction = $crop->k_fertilizer_fraction ?? 0.60; // MOP  0-0-60

        return [
            'n' => [
                'target'          => round($nTarget, 2),
                'current'         => round($n, 2),
                'deficit'         => round($nDeficit, 2),
                'fraction'        => $nFraction,
                'fertilizer_kgha' => self::fertilizerAmount($nDeficit, $nFraction),
            ],
            'p' => [
                'target'          => round($pTarget, 2),
                'current'         => round($p, 2),
                'deficit'         => round($pDeficit, 2),
                'fraction'        => $pFraction,
                'fertilizer_kgha' => self::fertilizerAmount($pDeficit, $pFraction),
            ],
            'k' => [
                'target'          => round($kTarget, 2),
                'current'         => round($k, 2),
                'deficit'         => round($kDeficit, 2),
                'fraction'        => $kFraction,
                'fertilizer_kgha' => self::fertilizerAmount($kDeficit, $kFraction),
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // PPM → kg/ha CONVERSION
    // -----------------------------------------------------------------------

    /**
     * Convert soil nutrient concentration (ppm / mg kg⁻¹) to kg/ha.
     *
     * Standard conversion factor (BSWM / IRRI):
     *   kg/ha = ppm × bulk_density (g/cm³) × depth (cm) × 0.1
     *
     * Default assumptions (standard plough layer):
     *   bulk_density = 1.3 g/cm³  (average Philippine mineral soil)
     *   depth        = 20 cm      (standard plough layer)
     *   factor       = 1.3 × 20 × 0.1 = 2.6
     *
     * So:  kg/ha = ppm × 2.6
     *
     * @param  float  $ppm
     * @param  float  $bulkDensity  g/cm³ (default 1.3)
     * @param  float  $depthCm      Soil depth in cm (default 20)
     * @return float  kg/ha
     */
    public static function ppmToKgPerHa(float $ppm, float $bulkDensity = 1.3, float $depthCm = 20.0): float
    {
        return round($ppm * $bulkDensity * $depthCm * 0.1, 2);
    }
}
