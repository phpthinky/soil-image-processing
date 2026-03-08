<?php

namespace App\Services;

use App\Models\PhColorChart;

/**
 * Color science: CIE L*a*b* conversion + CIEDE2000 color distance.
 * Ported directly from old-app/config.php.
 */
class ColorScienceService
{
    // ── BSWM Reference Color Charts ─────────────────────────────────

    public const PH_COLOR_CHART = [
        '#FF3300' => 4.5, '#FF5500' => 4.8, '#FF7700' => 5.0,
        '#FFAA00' => 5.3, '#FFCC00' => 5.5, '#FFEE00' => 6.0,
        '#CCCC00' => 6.3, '#99CC00' => 6.5, '#66AA00' => 7.0,
        '#009944' => 7.3, '#009999' => 7.5, '#0077BB' => 8.0,
        '#0044AA' => 8.3, '#003388' => 8.5,
    ];

    /**
     * CPR (Cresol Red + Phenolphthalein) indicator — BSWM Step 1.
     * Range: pH 4.8–6.0  (7 discrete points matching the BSWM CPR color card).
     * CPR produces amber-yellow hues in this range, distinctly different from
     * the universal-indicator colors in PH_COLOR_CHART.
     *
     * Hex values manually measured from the physical BSWM kit card under
     * calibrated box-lighting conditions. Multiple entries per pH point
     * improve CIEDE2000 nearest-match accuracy.
     */
    public const CPR_COLOR_CHART = [
        '#FF8800' => 4.8,
        '#D2A65A' => 5.0,
        '#FFC800' => 5.2,
        '#B0622D' => 5.4,
        '#B0612C' => 5.4,
        '#EDE800' => 5.6,
        '#9D2529' => 5.8,
        '#A12D31' => 5.8,
        '#7E2938' => 6.0,
        '#7E2939' => 6.0,
    ];

    /**
     * BCG (Bromocresol Green) indicator — BSWM Step 2, acidic soils.
     * Range: pH 4.0–5.4  (8 discrete points).
     * BCG transitions from yellow at low pH to teal/blue at pH 5.4.
     *
     * Hex values manually measured from the physical BSWM kit card under
     * calibrated box-lighting conditions. Multiple entries per pH point
     * improve CIEDE2000 nearest-match accuracy.
     */
    public const BCG_COLOR_CHART = [
        '#798136' => 4.0,
        '#7C843A' => 4.0,
        '#CCCC00' => 4.2,
        '#47806C' => 4.4,
        '#417C67' => 4.4,
        '#22AA33' => 4.6,
        '#548976' => 4.8,
        '#457F6C' => 4.8,
        '#46806C' => 4.8,
        '#009966' => 5.0,
        '#596394' => 5.2,
        '#576292' => 5.2,
        '#0066BB' => 5.4,
    ];

    /**
     * BTB (Bromothymol Blue) indicator — BSWM Step 2, near-neutral soils.
     * Range: pH 6.0–7.6  (5 discrete points).
     * BTB transitions from yellow at pH 6.0 to blue at pH 7.6.
     *
     * NOTE: Approximate reference values — no physical BTB card calibration
     * performed yet. Recalibrate by measuring BTB card strips under the same
     * box+lighting conditions used for CPR/BCG captures.
     */
    public const BTB_COLOR_CHART = [
        '#DDDD00' => 6.0,
        '#DCDC00' => 6.0,
        '#88BB00' => 6.4,
        '#33AA44' => 6.8,
        '#009977' => 7.2,
        '#0066CC' => 7.6,
        '#0055BB' => 7.6,
    ];

    public const NITROGEN_COLOR_CHART = [
        '#FFF5F5' =>  2.0, '#FFE0E8' =>  8.0, '#FFB3C6' => 15.0,
        '#FF80A0' => 22.0, '#FF4D80' => 30.0, '#E6006B' => 40.0,
        '#CC0066' => 50.0, '#990066' => 60.0, '#660066' => 70.0,
        '#440044' => 80.0,
    ];

    public const PHOSPHORUS_COLOR_CHART = [
        '#FEFEFE' =>  1.0, '#EEF8FF' =>  3.0, '#D4EEFF' =>  5.0,
        '#A8D8F0' =>  8.0, '#70BAE8' => 12.0, '#42A5F5' => 18.0,
        '#1E88E5' => 25.0, '#1565C0' => 35.0, '#0D47A1' => 45.0,
        '#062A70' => 55.0,
    ];

    public const POTASSIUM_COLOR_CHART = [
        '#0A0A0A' =>   5.0, '#2A2A2A' => 15.0, '#555555' => 25.0,
        '#808080' =>  40.0, '#AAAAAA' => 60.0, '#C8C8C8' => 80.0,
        '#DEDEDE' =>  95.0, '#F0F0F0' => 110.0, '#FAFAFA' => 120.0,
    ];

    // ── Public API ───────────────────────────────────────────────────

    public function colorToPhLevel(string $hex): float
    {
        return round(min(14.0, max(0.0, $this->matchColorToValue($hex, self::PH_COLOR_CHART))), 1);
    }

    /**
     * Like colorToPhLevel() but also returns the color match confidence percentage
     * derived from the minimum CIEDE2000 distance to any reference color.
     * Returns ['ph' => float, 'confidence_pct' => int (0–100)].
     */
    public function colorToPhLevelWithConfidence(string $hex): array
    {
        [$value, $minDeltaE] = $this->matchColorToValueWithDeltaE($hex, self::PH_COLOR_CHART);
        $ph            = round(min(14.0, max(0.0, $value)), 1);
        $confidencePct = max(0, min(100, (int) round(100 - $minDeltaE * 3)));
        return ['ph' => $ph, 'confidence_pct' => $confidencePct];
    }

    /**
     * Compute pH from a CPR/BCG/BTB indicator color using the indicator-specific
     * reference chart instead of the general PH_COLOR_CHART.
     *
     * Using the general chart for pH-test captures produces a systematic ~0.6 pH
     * overestimate because universal-indicator orange (pH 5.0 in PH_COLOR_CHART)
     * does not match the amber-yellow hue that CPR produces at pH 5.0.
     *
     * @param  string $hex       Captured color hex (e.g. '#FFB200')
     * @param  string $solution  'CPR', 'BCG', or 'BTB'
     * @return array{ph: float, confidence_pct: int}
     */
    public function phTestColorToPhLevel(string $hex, string $solution): array
    {
        $solution = strtoupper($solution);

        // Load active reference colors from DB; fall back to hardcoded constants
        // if the table is empty (e.g. before seeding or during testing).
        $chart = PhColorChart::chartForIndicator($solution);
        if (empty($chart)) {
            $chart = match ($solution) {
                'BCG'   => self::BCG_COLOR_CHART,
                'BTB'   => self::BTB_COLOR_CHART,
                default => self::CPR_COLOR_CHART,
            };
        }

        [$value, $minDeltaE] = $this->matchColorToValueWithDeltaE($hex, $chart);

        // Clamp to the valid measurable range for each indicator
        $ph = match ($solution) {
            'BCG'   => round(min(5.5, max(3.9, $value)), 1),
            'BTB'   => round(min(7.7, max(5.9, $value)), 1),
            default => round(min(6.1, max(4.7, $value)), 1),  // CPR
        };

        $confidencePct = max(0, min(100, (int) round(100 - $minDeltaE * 3)));

        return ['ph' => $ph, 'confidence_pct' => $confidencePct];
    }

    public function colorToNitrogenLevel(string $hex): float
    {
        return round(min(100.0, max(0.0, $this->matchColorToValue($hex, self::NITROGEN_COLOR_CHART))), 2);
    }

    public function colorToPhosphorusLevel(string $hex): float
    {
        return round(min(100.0, max(0.0, $this->matchColorToValue($hex, self::PHOSPHORUS_COLOR_CHART))), 2);
    }

    public function colorToPotassiumLevel(string $hex): float
    {
        return round(min(100.0, max(0.0, $this->matchColorToValue($hex, self::POTASSIUM_COLOR_CHART))), 2);
    }

    public function computeForParameter(string $parameter, string $hex): float
    {
        return match ($parameter) {
            'ph'         => $this->colorToPhLevel($hex),
            'nitrogen'   => $this->colorToNitrogenLevel($hex),
            'phosphorus' => $this->colorToPhosphorusLevel($hex),
            'potassium'  => $this->colorToPotassiumLevel($hex),
        };
    }

    // ── Core color math ──────────────────────────────────────────────

    public function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    public function rgbToLab(int $r, int $g, int $b): array
    {
        $r /= 255.0; $g /= 255.0; $b /= 255.0;
        $lin = fn($c) => $c > 0.04045 ? pow(($c + 0.055) / 1.055, 2.4) : $c / 12.92;
        $r = $lin($r); $g = $lin($g); $b = $lin($b);

        $x = $r * 0.4124564 + $g * 0.3575761 + $b * 0.1804375;
        $y = $r * 0.2126729 + $g * 0.7151522 + $b * 0.0721750;
        $z = $r * 0.0193339 + $g * 0.1191920 + $b * 0.9503041;
        $x /= 0.95047; $y /= 1.00000; $z /= 1.08883;

        $f = fn($t) => $t > 0.008856 ? pow($t, 1.0 / 3.0) : (7.787 * $t + 16.0 / 116.0);
        return [
            'L' => round(116.0 * $f($y) - 16.0, 4),
            'a' => round(500.0 * ($f($x) - $f($y)), 4),
            'b' => round(200.0 * ($f($y) - $f($z)), 4),
        ];
    }

    public function deltaE2000(array $lab1, array $lab2): float
    {
        [$L1, $a1, $b1] = [$lab1['L'], $lab1['a'], $lab1['b']];
        [$L2, $a2, $b2] = [$lab2['L'], $lab2['a'], $lab2['b']];

        $C1ab = sqrt($a1 ** 2 + $b1 ** 2);
        $C2ab = sqrt($a2 ** 2 + $b2 ** 2);
        $Cab  = ($C1ab + $C2ab) / 2.0;
        $Cab7 = $Cab ** 7;
        $G    = 0.5 * (1.0 - sqrt($Cab7 / ($Cab7 + 25.0 ** 7)));

        $a1p = $a1 * (1.0 + $G); $a2p = $a2 * (1.0 + $G);
        $C1p = sqrt($a1p ** 2 + $b1 ** 2);
        $C2p = sqrt($a2p ** 2 + $b2 ** 2);

        $h1p = ($b1 == 0 && $a1p == 0) ? 0.0 : atan2($b1, $a1p) * 180.0 / M_PI;
        if ($h1p < 0) $h1p += 360.0;
        $h2p = ($b2 == 0 && $a2p == 0) ? 0.0 : atan2($b2, $a2p) * 180.0 / M_PI;
        if ($h2p < 0) $h2p += 360.0;

        $dLp = $L2 - $L1;
        $dCp = $C2p - $C1p;

        if ($C1p * $C2p == 0.0) {
            $dhp = 0.0;
        } elseif (abs($h2p - $h1p) <= 180.0) {
            $dhp = $h2p - $h1p;
        } elseif ($h2p - $h1p > 180.0) {
            $dhp = $h2p - $h1p - 360.0;
        } else {
            $dhp = $h2p - $h1p + 360.0;
        }
        $dHp = 2.0 * sqrt($C1p * $C2p) * sin(deg2rad($dhp / 2.0));

        $Lbp = ($L1 + $L2) / 2.0;
        $Cbp = ($C1p + $C2p) / 2.0;
        if ($C1p * $C2p == 0.0) {
            $Hbp = $h1p + $h2p;
        } elseif (abs($h1p - $h2p) <= 180.0) {
            $Hbp = ($h1p + $h2p) / 2.0;
        } elseif ($h1p + $h2p < 360.0) {
            $Hbp = ($h1p + $h2p + 360.0) / 2.0;
        } else {
            $Hbp = ($h1p + $h2p - 360.0) / 2.0;
        }

        $T = 1.0 - 0.17 * cos(deg2rad($Hbp - 30.0))
               + 0.24 * cos(deg2rad(2.0 * $Hbp))
               + 0.32 * cos(deg2rad(3.0 * $Hbp + 6.0))
               - 0.20 * cos(deg2rad(4.0 * $Hbp - 63.0));

        $SL = 1.0 + 0.015 * ($Lbp - 50.0) ** 2 / sqrt(20.0 + ($Lbp - 50.0) ** 2);
        $SC = 1.0 + 0.045 * $Cbp;
        $SH = 1.0 + 0.015 * $Cbp * $T;

        $Cbp7   = $Cbp ** 7;
        $RC     = 2.0 * sqrt($Cbp7 / ($Cbp7 + 25.0 ** 7));
        $dTheta = 30.0 * exp(-(($Hbp - 275.0) / 25.0) ** 2);
        $RT     = -$RC * sin(deg2rad(2.0 * $dTheta));

        return sqrt(
            ($dLp / $SL) ** 2 +
            ($dCp / $SC) ** 2 +
            ($dHp / $SH) ** 2 +
            $RT * ($dCp / $SC) * ($dHp / $SH)
        );
    }

    public function matchColorToValue(string $capturedHex, array $chart): float
    {
        return $this->matchColorToValueWithDeltaE($capturedHex, $chart)[0];
    }

    /**
     * Same as matchColorToValue() but also returns the minimum CIEDE2000 distance
     * to any reference color as the second element: [float $value, float $minDeltaE].
     */
    private function matchColorToValueWithDeltaE(string $capturedHex, array $chart): array
    {
        $rgb = $this->hexToRgb($capturedHex);
        $lab = $this->rgbToLab($rgb['r'], $rgb['g'], $rgb['b']);

        $distances = [];
        foreach ($chart as $refHex => $refValue) {
            $rRgb        = $this->hexToRgb($refHex);
            $rLab        = $this->rgbToLab($rRgb['r'], $rRgb['g'], $rRgb['b']);
            $distances[] = ['value' => $refValue, 'de' => $this->deltaE2000($lab, $rLab)];
        }
        usort($distances, fn($a, $b) => $a['de'] <=> $b['de']);

        $minDeltaE = $distances[0]['de'];

        if ($minDeltaE < 0.5) {
            return [(float) $distances[0]['value'], $minDeltaE];
        }

        $top = array_slice($distances, 0, 3);
        $num = $denom = 0.0;
        foreach ($top as $t) {
            $w     = 1.0 / max($t['de'], 0.001);
            $num  += $w * $t['value'];
            $denom += $w;
        }
        return [round($num / $denom, 2), $minDeltaE];
    }
}
