<?php

namespace App\Services;

use App\Models\PhColorChart;

class PhTestService
{
    // Variance threshold: readings within 0.3 pH units = High confidence
    private const VARIANCE_THRESHOLD = 0.09; // (0.3)^2

    /**
     * Fixed paper chart pH values per indicator solution.
     * These match the discrete color reference points printed on BSWM soil test kit cards.
     */
    public const CHART_POINTS = [
        'CPR' => [4.8, 5.0, 5.2, 5.4, 5.6, 5.8, 6.0],
        'BCG' => [4.0, 4.2, 4.4, 4.6, 4.8, 5.0, 5.2, 5.4],
        'BTB' => [6.0, 6.2, 6.4, 6.8, 7.2, 7.8],
    ];

    /**
     * Snap a continuous scientific pH value to the nearest card point that is
     * >= the scientific value (ceiling-snap).
     *
     * This guarantees chart_ph >= scientific_ph so that the technician always
     * sees the card reading at or above the spectrophotometric value — never below it.
     * Round-up on tie is no longer needed; the ceiling rule covers it naturally.
     *
     * If scientific pH exceeds every chart point (out-of-range high), the highest
     * chart point is returned (clamped). Chart points are loaded from the
     * ph_color_charts DB table; falls back to CHART_POINTS constant if DB is empty.
     */
    public static function snapToChartPh(float $ph, string $solution): float
    {
        $dbPoints = PhColorChart::chartPointsForIndicator($solution);
        $points   = !empty($dbPoints) ? $dbPoints : (self::CHART_POINTS[strtoupper($solution)] ?? self::CHART_POINTS['CPR']);

        // Points are sorted ascending. Return the first point >= scientific pH.
        foreach ($points as $point) {
            if ($point >= $ph - 0.00001) {
                return $point;
            }
        }

        // Scientific pH exceeds all chart points → clamp to highest card value.
        return end($points);
    }

    /**
     * Human-readable soil pH interpretation used in the result panel.
     */
    public static function phInterpretation(float $ph): string
    {
        return match(true) {
            $ph < 4.5 => 'Extremely Acidic',
            $ph < 5.0 => 'Strongly Acidic',
            $ph < 5.5 => 'Moderately Acidic',
            $ph < 6.0 => 'Slightly Acidic',
            $ph < 6.5 => 'Near Neutral (Slightly Acidic)',
            $ph < 7.0 => 'Near Neutral',
            $ph < 7.5 => 'Neutral to Slightly Alkaline',
            $ph < 8.0 => 'Moderately Alkaline',
            default   => 'Strongly Alkaline',
        };
    }

    /**
     * Determine the next solution based on CPR pH reading.
     * Per BSWM protocol:
     *   pH ≤ 5.4  → BCG (Bromocresol Green)
     *   pH > 5.8  → BTB (Bromothymol Blue)
     *   5.4 < pH ≤ 5.8 → borderline; use CPR result as final, no second solution needed
     *   pH < 4.0 or pH > 7.6 → outside chart range, retest
     */
    public function decideSolution(float $ph1): string
    {
        if ($ph1 < 4.0 || $ph1 > 7.8) return 'RETEST';
        if ($ph1 <= 5.4) return 'BCG';
        if ($ph1 > 5.8)  return 'BTB';
        // 5.4 < pH ≤ 5.8: per BSWM protocol the CPR reading is accepted as final
        return 'CPR';
    }

    /**
     * Compute stats from an array of pH float values.
     * Returns [average, median, variance, confidence].
     */
    public function computeStats(array $values): array
    {
        $n = count($values);
        if ($n === 0) return ['average' => 0, 'median' => 0, 'variance' => 0, 'confidence' => 'Low'];

        $avg = array_sum($values) / $n;

        $sorted = $values;
        sort($sorted);
        $median = $n % 2 === 0
            ? ($sorted[$n / 2 - 1] + $sorted[$n / 2]) / 2
            : $sorted[(int)($n / 2)];

        $variance = $n > 1
            ? array_sum(array_map(fn($v) => ($v - $avg) ** 2, $values)) / $n
            : 0.0;

        $confidence = $variance < self::VARIANCE_THRESHOLD ? 'High' : 'Low';

        return [
            'average'    => round($avg, 2),
            'median'     => round($median, 2),
            'variance'   => round($variance, 4),
            'confidence' => $confidence,
        ];
    }

    /**
     * Average the RGB values from a readings array and return hex.
     */
    public function averageHex(array $readings): string
    {
        if (empty($readings)) return '#000000';
        $r = (int) round(array_sum(array_column($readings, 'r')) / count($readings));
        $g = (int) round(array_sum(array_column($readings, 'g')) / count($readings));
        $b = (int) round(array_sum(array_column($readings, 'b')) / count($readings));
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Human-readable description for the solution decision.
     */
    public function solutionDescription(string $solution): string
    {
        return match($solution) {
            'BCG'    => 'BCG (Bromocresol Green) — Confirms pH in the acidic range (4.0–5.4)',
            'BTB'    => 'BTB (Bromothymol Blue) — Confirms pH in the near-neutral range (5.8–7.6)',
            'CPR'    => 'CPR Result is Final — pH in transitional range (5.4–5.8); no second test needed',
            'RETEST' => 'Retest Required — pH is outside the measurable chart range (4.0–7.8)',
            default  => 'Unknown',
        };
    }

    /**
     * Timer recommendation in seconds for each solution's color development.
     * CPR: 1 min mix + 2 min stand + 1 min mix + 5 min stand = ~8 min total wait
     * BCG/BTB: repeat steps 1–6 with the same timing as CPR
     */
    public function reactionTimer(string $solution): int
    {
        return match($solution) {
            'BCG'    => 480, // 8 minutes
            'BTB'    => 480, // 8 minutes
            default  => 480, // CPR — 8 minutes total
        };
    }

    /**
     * Generate outcome key and remarks after Step 1 (CPR) completes.
     *
     * Outcome keys:
     *   win-bcg     → pH1 in BCG range (5.0–5.4), proceed with BCG — win
     *   win-btb     → pH1 in BTB range (5.8–6.0), proceed with BTB — win
     *   retest      → pH1 outside both ranges, retest needed
     *   high-acid   → pH1 very low (< 4.5)
     *   alkaline    → pH1 very high (> 6.5)
     */
    public function generateStep1Remarks(float $ph1, string $nextSolution, string $confidence): array
    {
        $confNote = $confidence === 'High'
            ? 'Readings are consistent (high confidence).'
            : 'Readings show variability (low confidence) — proceed carefully.';

        if ($nextSolution === 'BCG') {
            return [
                'outcome' => 'win-bcg',
                'remarks' => "✔ Win — CPR pH reading ({$ph1}) is ≤ 5.4. "
                           . "Proceed with Bromocresol Green (BCG) solution for Step 2 confirmation. "
                           . $confNote,
            ];
        }

        if ($nextSolution === 'BTB') {
            return [
                'outcome' => 'win-btb',
                'remarks' => "✔ Win — CPR pH reading ({$ph1}) is > 5.8. "
                           . "Proceed with Bromothymol Blue (BTB) solution for Step 2 confirmation. "
                           . $confNote,
            ];
        }

        if ($nextSolution === 'CPR') {
            return [
                'outcome' => 'win-cpr',
                'remarks' => "✔ Win — CPR pH reading ({$ph1}) falls in the transitional range (5.4–5.8). "
                           . "Per BSWM protocol, the CPR result is recorded as the final pH value. "
                           . "No second solution test is required. " . $confNote,
            ];
        }

        // RETEST — outside measurable range (< 4.0 or > 7.6)
        if ($ph1 < 4.0) {
            return [
                'outcome' => 'high-acid',
                'remarks' => "⚠ Retest Required — CPR pH reading ({$ph1}) is below the BCG chart minimum (4.0), "
                           . "which is outside the measurable range. "
                           . "Verify soil strip placement and repeat with a fresh sample.",
            ];
        }

        return [
            'outcome' => 'alkaline',
            'remarks' => "⚠ Retest Required — CPR pH reading ({$ph1}) exceeds the BTB chart maximum (7.8), "
                       . "which is outside the measurable range. "
                       . "Verify soil strip placement and repeat with a fresh sample.",
        ];
    }

    /**
     * Generate outcome key and remarks after Step 2 (BCG/BTB) completes.
     *
     * Outcome keys:
     *   confirmed     → pH2 consistent with pH1 and expected range
     *   borderline    → pH2 within range but close to boundary
     *   inconsistent  → pH2 outside expected range for selected solution
     */
    public function generateStep2Remarks(float $ph1, float $ph2, string $solution, string $confidence): array
    {
        $confNote = $confidence === 'High'
            ? 'Step 2 readings are consistent (high confidence).'
            : 'Step 2 readings show variability (low confidence).';

        $diff = abs($ph1 - $ph2);

        // Check if pH2 is within the expected range for the solution
        $inRange = match($solution) {
            'BCG' => $ph2 >= 4.0 && $ph2 <= 5.2,
            'BTB' => $ph2 >= 6.0 && $ph2 <= 7.8,
            default => true,
        };

        if (!$inRange) {
            return [
                'outcome' => 'inconsistent',
                'remarks' => "⚠ Inconsistent — {$solution} pH reading ({$ph2}) is outside the expected range for "
                           . ($solution === 'BCG' ? 'BCG (4.0–5.2)' : 'BTB (6.0–7.8)') . ". "
                           . "The CPR reading was {$ph1}. Consider retesting. " . $confNote,
            ];
        }

        if ($diff > 0.5) {
            return [
                'outcome' => 'borderline',
                'remarks' => "~ Borderline — {$solution} pH reading ({$ph2}) is within range but differs from CPR ({$ph1}) "
                           . "by " . number_format($diff, 2) . " pH units. "
                           . "Final pH averaged as " . number_format(($ph1 + $ph2) / 2, 2) . ". " . $confNote,
            ];
        }

        return [
            'outcome' => 'confirmed',
            'remarks' => "✔ Confirmed — {$solution} pH reading ({$ph2}) is consistent with CPR ({$ph1}). "
                       . "The pH is reliably determined. Final pH = " . number_format($ph2, 2) . ". " . $confNote,
        ];
    }
}
