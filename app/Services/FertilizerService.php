<?php

namespace App\Services;

/**
 * BSWM/PhilRice fertilizer recommendation engine.
 * Ported from old-app/config.php getFertilizerRecommendation().
 */
class FertilizerService
{
    public function recommend(float $ph, float $n, float $p, float $k): array
    {
        $rec = ['lime_tons' => 0.0, 'urea_bags' => 0.0, 'tsp_bags' => 0.0, 'mop_bags' => 0.0, 'notes' => []];

        // Lime for pH correction
        if ($ph < 5.0) {
            $rec['lime_tons'] = 2.0;
            $rec['notes'][]   = 'Soil is strongly acidic (pH < 5.0). Apply 2 t/ha dolomitic lime at least 2 weeks before planting.';
        } elseif ($ph < 5.5) {
            $rec['lime_tons'] = 1.0;
            $rec['notes'][]   = 'Soil is moderately acidic (pH 5.0–5.5). Apply 1 t/ha dolomitic lime to improve nutrient availability.';
        } elseif ($ph > 7.5) {
            $rec['notes'][]   = 'Soil is alkaline (pH > 7.5). Consider incorporating organic matter or elemental sulfur to lower pH.';
        }

        // Nitrogen (Urea 46-0-0)
        if ($n < 20) {
            $rec['urea_bags'] = 4.0;
            $rec['notes'][]   = 'Low nitrogen. Apply Urea in 2 splits: ½ basal + ½ at panicle initiation.';
        } elseif ($n < 40) {
            $rec['urea_bags'] = 2.5;
            $rec['notes'][]   = 'Medium nitrogen. Apply Urea in 2 splits: ½ basal + ½ at active tillering.';
        } else {
            $rec['urea_bags'] = 1.0;
            $rec['notes'][]   = 'Adequate nitrogen. Apply minimal Urea (1 bag/ha) as maintenance only.';
        }

        // Phosphorus (TSP 0-46-0)
        if ($p < 15) {
            $rec['tsp_bags'] = 2.5;
            $rec['notes'][]  = 'Low phosphorus. Apply TSP basally (at planting) for root development.';
        } elseif ($p < 30) {
            $rec['tsp_bags'] = 1.5;
            $rec['notes'][]  = 'Medium phosphorus. Apply TSP basally to maintain P availability.';
        } else {
            $rec['tsp_bags'] = 0.0;
            $rec['notes'][]  = 'Adequate phosphorus. No TSP needed this season.';
        }

        // Potassium (MOP 0-0-60)
        if ($k < 20) {
            $rec['mop_bags'] = 2.0;
            $rec['notes'][]  = 'Low potassium. Apply MOP basally. K improves drought tolerance and grain quality.';
        } elseif ($k < 40) {
            $rec['mop_bags'] = 1.0;
            $rec['notes'][]  = 'Medium potassium. Apply 1 bag MOP/ha as basal application.';
        } else {
            $rec['mop_bags'] = 0.0;
            $rec['notes'][]  = 'Adequate potassium. No MOP needed this season.';
        }

        $rec['notes'][] = 'Recommendation basis: BSWM/PhilRice colorimetric soil test guidelines (per hectare). Verify with a certified soil laboratory for large-scale production decisions.';

        return $rec;
    }

    public function summary(array $rec): string
    {
        return sprintf(
            'Lime: %.1f t/ha | Urea (46-0-0): %.1f bags/ha | TSP (0-46-0): %.1f bags/ha | MOP (0-0-60): %.1f bags/ha',
            $rec['lime_tons'], $rec['urea_bags'], $rec['tsp_bags'], $rec['mop_bags']
        );
    }

    public function getNutrientStatus(string $parameter, float $value): string
    {
        $thresholds = [
            'ph'         => ['low_max' => 5.5,  'high_min' => 7.0],
            'nitrogen'   => ['low_max' => 20.0, 'high_min' => 40.0],
            'phosphorus' => ['low_max' => 15.0, 'high_min' => 30.0],
            'potassium'  => ['low_max' => 20.0, 'high_min' => 40.0],
        ];
        if (!isset($thresholds[$parameter])) return 'Medium';
        $t = $thresholds[$parameter];
        if ($parameter === 'ph') {
            if ($value < $t['low_max'])  return 'Acidic';
            if ($value > $t['high_min']) return 'Alkaline';
            return 'Optimal';
        }
        if ($value < $t['low_max'])   return 'Low';
        if ($value >= $t['high_min']) return 'High';
        return 'Medium';
    }

    public function computeFertilityScore(float $ph, float $n, float $p, float $k): int
    {
        $phScore = match (true) {
            $ph >= 6.0 && $ph <= 7.0 => 100,
            $ph >= 5.5 && $ph <= 7.5 => 70,
            $ph >= 5.0 && $ph <= 8.0 => 40,
            default => 10,
        };
        $nScore = match (true) {
            $n >= 20 && $n <= 40  => 100,
            $n > 40 && $n <= 60   => 80,
            $n >= 10              => 50,
            default               => 15,
        };
        $pScore = match (true) {
            $p >= 15 && $p <= 30  => 100,
            $p > 30 && $p <= 50   => 75,
            $p >= 8               => 50,
            default               => 15,
        };
        $kScore = match (true) {
            $k >= 20 && $k <= 40  => 100,
            $k > 40 && $k <= 70   => 75,
            $k >= 10              => 50,
            default               => 15,
        };
        return (int) round($nScore * 0.35 + $pScore * 0.25 + $kScore * 0.25 + $phScore * 0.15);
    }
}
