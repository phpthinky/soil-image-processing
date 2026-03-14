<?php

namespace App\Helpers;

class CropFertilizerHelper
{
    /**
     * 1️⃣ Reversed LMH classification
     * High soil → Low fertilizer need
     * Low soil → High fertilizer need
     */
  /*
    public static function classifyReversed(float $soilValue, float $cropLow, float $cropMed, float $cropHigh): string
    {
        if ($soilValue >= $cropHigh) return 'Low';
        if ($soilValue >= $cropMed) return 'Medium';
        return 'High';
    }
*/
    public static function classifyReversed(float $soilValue, float $cropLow, float $cropMed, float $cropHigh): string
{
    if ($soilValue >= $cropLow)  return 'Low';
    if ($soilValue >= $cropMed)  return 'Medium';
    return 'High';
}

    /**
     * 2️⃣ Compute deficit (target – soil)
     */
    public static function computeDeficit(float $soilValue, float $targetValue): float
    {
        return max(0, $targetValue - $soilValue);
    }

    /**
     * 3️⃣ Return crop target based on reversed status
     */
/*
    public static function getTargetByStatus(string $status, float $low, float $med, float $high): float
    {
        return match($status) {
            'Low' => $low,
            'Medium' => $med,
            'High' => $high,
        };
    }
*/
    public static function getTargetByStatus(string $status, float $low, float $med, float $high): float
{
    return match($status) {
        'Low'    => $low,   // soil >= low threshold, target = low (deficit = 0 or near 0)
        'Medium' => $med,   // needs to reach med threshold
        'High'   => $low,   // well below, target the full low threshold for proper amendment
    };
}
    /**
     * 4️⃣ Main computeFertilizer function
     * Returns status, target, deficit, and fertilizer kg/ha
     *
     * @param float $soilN
     * @param float $soilP
     * @param float $soilK
     * @param \App\Models\Crop $crop
     * @param float $soilMassKg default 2_000_000 kg/ha
     * @return array
     */
    public static function computeFertilizer(float $soilN, float $soilP, float $soilK, $crop, float $soilMassKg = 2000000): array
    {
        $result = [];

        $nutrients = [
            'n' => ['soil' => $soilN, 'low' => $crop->n_low, 'med' => $crop->n_med, 'high' => $crop->n_high, 'fraction' => 0.46], // Urea
            'p' => ['soil' => $soilP, 'low' => $crop->p_low, 'med' => $crop->p_med, 'high' => $crop->p_high, 'fraction' => 0.18], // DAP
            'k' => ['soil' => $soilK, 'low' => $crop->k_low, 'med' => $crop->k_med, 'high' => $crop->k_high, 'fraction' => 0.60], // MOP
        ];

        /*
        foreach ($nutrients as $key => $data) {
            // a) Determine reversed LMH status
            $status = self::classifyReversed($data['soil'], $data['low'], $data['med'], $data['high']);

            // b) Get crop target
            $target = self::getTargetByStatus($status, $data['low'], $data['med'], $data['high']);

            // c) Compute deficit
            $deficit = self::computeDeficit($data['soil'], $target);

            // d) Convert deficit ppm → kg/ha
            $fertKgHa = ($deficit / 1_000_000) * $soilMassKg / $data['fraction'];

            $result[$key] = [
                'status' => $status,
                'target_ppm' => $target,
                'soil_ppm' => $data['soil'],
                'deficit_ppm' => round($deficit, 2),
                'fert_kg_ha' => round($fertKgHa, 2),
            ];
         }*/
                        foreach ($nutrients as $key => $data) {
                    // DB stores thresholds in kg/ha — convert to ppm for comparison with soil readings
                    // kg/ha ÷ 2 = ppm  (1 ppm ≈ 2 kg/ha at 0–15 cm, 2,000,000 kg soil/ha)
                    $lowPpm  = $data['low']  / 2;
                    $medPpm  = $data['med']  / 2;
                    $highPpm = $data['high'] / 2;

                    $status = self::classifyReversed($data['soil'], $lowPpm, $medPpm, $highPpm);
                    $target = self::getTargetByStatus($status, $lowPpm, $medPpm, $highPpm);
                    $deficit = self::computeDeficit($data['soil'], $target);

                    // deficit is already in ppm — convert to kg/ha for fertilizer calculation
                    $deficitKgHa = $deficit * 2;
                    $fertKgHa = $deficitKgHa / $data['fraction'];

                    $result[$key] = [
                        'status'      => $status,
                        'target_ppm'  => round($target, 2),
                        'target_kgha' => round($target * 2, 2),   // for display
                        'soil_ppm'    => $data['soil'],
                        'deficit_ppm' => round($deficit, 2),
                        'deficit_kgha'=> round($deficitKgHa, 2),
                        'fert_kg_ha'  => round($fertKgHa, 2),
                    ];
                }

        return $result;
    }
}