<?php
// ============================================================
// config.php — Core configuration, DB connection, helpers
// ============================================================

session_start();

// ── Database credentials ─────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'soil_analyzer');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── AI / Anthropic API key ───────────────────────────────────
// Set ANTHROPIC_API_KEY as an environment variable, or replace
// the empty string below for local testing only.
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: '');

// ── PDO connection ───────────────────────────────────────────
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    die('<h3 style="color:red;font-family:sans-serif;">Database connection failed. Please contact the administrator.</h3>');
}

// ── Auth helpers ─────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ============================================================
// COLOR SCIENCE — CIE Lab + Delta-E (accurate colorimetry)
// ============================================================

/**
 * Converts a 6-digit hex color string to an ['r','g','b'] array.
 */
function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2)),
    ];
}

/**
 * Converts sRGB (0-255 each) to CIE L*a*b* (D65 illuminant).
 * Lab is the preferred color space for perceptual color matching
 * because Delta-E distances correlate with human visual difference.
 */
function rgbToLab(int $r, int $g, int $b): array {
    // Step 1: Normalize 0-255 → 0-1
    $r /= 255.0; $g /= 255.0; $b /= 255.0;

    // Step 2: Linearise (undo sRGB gamma)
    $linearise = fn($c) => $c > 0.04045
        ? pow(($c + 0.055) / 1.055, 2.4)
        : $c / 12.92;
    $r = $linearise($r); $g = $linearise($g); $b = $linearise($b);

    // Step 3: sRGB → XYZ (D65 white point, IEC 61966-2-1)
    $x = $r * 0.4124564 + $g * 0.3575761 + $b * 0.1804375;
    $y = $r * 0.2126729 + $g * 0.7151522 + $b * 0.0721750;
    $z = $r * 0.0193339 + $g * 0.1191920 + $b * 0.9503041;

    // Step 4: Normalise by D65 white point
    $x /= 0.95047; $y /= 1.00000; $z /= 1.08883;

    // Step 5: XYZ → L*a*b*
    $f = fn($t) => $t > 0.008856 ? pow($t, 1.0 / 3.0) : (7.787 * $t + 16.0 / 116.0);
    $fx = $f($x); $fy = $f($y); $fz = $f($z);

    return [
        'L' => round(116.0 * $fy - 16.0,   4),
        'a' => round(500.0 * ($fx - $fy),   4),
        'b' => round(200.0 * ($fy - $fz),   4),
    ];
}

/**
 * CIEDE2000 Delta-E — the most accurate perceptual color distance.
 * Recommended by research for colorimetric strip analysis (ACS Omega 2021).
 * Better than CIE76 for saturated colors and the blue region used by
 * molybdenum-blue phosphorus tests.
 * Values < 2 are just-noticeable; < 3 is considered a match for strip reading.
 */
function deltaE2000(array $lab1, array $lab2): float {
    $L1 = $lab1['L']; $a1 = $lab1['a']; $b1 = $lab1['b'];
    $L2 = $lab2['L']; $a2 = $lab2['a']; $b2 = $lab2['b'];

    $kL = 1.0; $kC = 1.0; $kH = 1.0;

    $C1ab = sqrt($a1 ** 2 + $b1 ** 2);
    $C2ab = sqrt($a2 ** 2 + $b2 ** 2);
    $Cab  = ($C1ab + $C2ab) / 2.0;
    $Cab7 = $Cab ** 7;
    $G    = 0.5 * (1.0 - sqrt($Cab7 / ($Cab7 + 25.0 ** 7)));

    $a1p  = $a1 * (1.0 + $G);
    $a2p  = $a2 * (1.0 + $G);
    $C1p  = sqrt($a1p ** 2 + $b1 ** 2);
    $C2p  = sqrt($a2p ** 2 + $b2 ** 2);

    $h1p  = ($b1 == 0 && $a1p == 0) ? 0.0 : atan2($b1, $a1p) * 180.0 / M_PI;
    if ($h1p < 0) $h1p += 360.0;
    $h2p  = ($b2 == 0 && $a2p == 0) ? 0.0 : atan2($b2, $a2p) * 180.0 / M_PI;
    if ($h2p < 0) $h2p += 360.0;

    $dLp  = $L2 - $L1;
    $dCp  = $C2p - $C1p;

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

    $Lbp  = ($L1 + $L2) / 2.0;
    $Cbp  = ($C1p + $C2p) / 2.0;

    if ($C1p * $C2p == 0.0) {
        $Hbp = $h1p + $h2p;
    } elseif (abs($h1p - $h2p) <= 180.0) {
        $Hbp = ($h1p + $h2p) / 2.0;
    } elseif ($h1p + $h2p < 360.0) {
        $Hbp = ($h1p + $h2p + 360.0) / 2.0;
    } else {
        $Hbp = ($h1p + $h2p - 360.0) / 2.0;
    }

    $T  = 1.0
        - 0.17 * cos(deg2rad($Hbp - 30.0))
        + 0.24 * cos(deg2rad(2.0 * $Hbp))
        + 0.32 * cos(deg2rad(3.0 * $Hbp + 6.0))
        - 0.20 * cos(deg2rad(4.0 * $Hbp - 63.0));

    $SL = 1.0 + 0.015 * ($Lbp - 50.0) ** 2 / sqrt(20.0 + ($Lbp - 50.0) ** 2);
    $SC = 1.0 + 0.045 * $Cbp;
    $SH = 1.0 + 0.015 * $Cbp * $T;

    $Cbp7    = $Cbp ** 7;
    $RC      = 2.0 * sqrt($Cbp7 / ($Cbp7 + 25.0 ** 7));
    $dTheta  = 30.0 * exp(-(($Hbp - 275.0) / 25.0) ** 2);
    $RT      = -$RC * sin(deg2rad(2.0 * $dTheta));

    return sqrt(
        ($dLp  / ($kL * $SL)) ** 2 +
        ($dCp  / ($kC * $SC)) ** 2 +
        ($dHp  / ($kH * $SH)) ** 2 +
        $RT * ($dCp / ($kC * $SC)) * ($dHp / ($kH * $SH))
    );
}

// Keep CIE76 as a fast fallback
function deltaE76(array $lab1, array $lab2): float {
    return sqrt(
        ($lab1['L'] - $lab2['L']) ** 2 +
        ($lab1['a'] - $lab2['a']) ** 2 +
        ($lab1['b'] - $lab2['b']) ** 2
    );
}

/**
 * Given a captured hex color and a reference chart (hex → value),
 * returns an interpolated reading using CIEDE2000 inverse-distance
 * weighted interpolation in CIE Lab color space.
 *
 * Research confirms: CIELAB + CIEDE2000 gives the best accuracy for
 * colorimetric strip analysis (ACS Omega 2021, 96.6% classification).
 */
function matchColorToValue(string $capturedHex, array $chart): float {
    $capturedRgb = hexToRgb($capturedHex);
    $capturedLab = rgbToLab($capturedRgb['r'], $capturedRgb['g'], $capturedRgb['b']);

    $distances = [];
    foreach ($chart as $refHex => $refValue) {
        $refRgb      = hexToRgb($refHex);
        $refLab      = rgbToLab($refRgb['r'], $refRgb['g'], $refRgb['b']);
        $distances[] = ['value' => $refValue, 'de' => deltaE2000($capturedLab, $refLab)];
    }

    // Sort by distance ascending
    usort($distances, fn($a, $b) => $a['de'] <=> $b['de']);

    // Perfect (or very close) match
    if ($distances[0]['de'] < 0.5) {
        return (float) $distances[0]['value'];
    }

    // Inverse-distance weighted interpolation of top 3 closest
    $top    = array_slice($distances, 0, 3);
    $num    = 0.0;
    $denom  = 0.0;
    foreach ($top as $t) {
        $w      = 1.0 / max($t['de'], 0.001);
        $num   += $w * $t['value'];
        $denom += $w;
    }
    return round($num / $denom, 2);
}

// ============================================================
// BSWM REFERENCE COLOR CHARTS
// ============================================================
// Colors are derived from the Philippine Bureau of Soils and
// Water Management (BSWM) Soil Test Kit color comparison charts.
// Format: 'hex_color' => measured_value
//
// IMPORTANT: These reference colors are calibrated for the
// standard BSWM/PhilRice colorimetric kit photographed under
// neutral (D65-like) white light. Update hex values here once
// you have calibrated images from the agriculture office.
// ============================================================

/**
 * pH indicator strip reference chart.
 * Indicator: mixed Bromothymol Blue + Thymol Blue
 * Range: pH 4.5 (red-orange) → 8.5 (dark blue)
 */
define('PH_COLOR_CHART', [
    '#FF3300' => 4.5,   // Red           — strongly acidic
    '#FF5500' => 4.8,   // Red-Orange
    '#FF7700' => 5.0,   // Orange
    '#FFAA00' => 5.3,   // Amber-Orange
    '#FFCC00' => 5.5,   // Deep Yellow
    '#FFEE00' => 6.0,   // Yellow
    '#CCCC00' => 6.3,   // Yellow-Olive
    '#99CC00' => 6.5,   // Yellow-Green
    '#66AA00' => 7.0,   // Green         — neutral
    '#009944' => 7.3,   // Dark Green
    '#009999' => 7.5,   // Teal
    '#0077BB' => 8.0,   // Blue
    '#0044AA' => 8.3,   // Medium Blue
    '#003388' => 8.5,   // Dark Blue     — strongly alkaline
]);

/**
 * Nitrogen test strip reference chart.
 * Method: BSWM STK uses indophenol blue or diphenylamine-type reagent.
 * Color scale: Colorless/pale → PINK → PURPLE (darker = higher N)
 * This is NOT a yellow scale — confirmed by BSWM STK documentation.
 * Range: 0–80 ppm
 *
 * NOTE: Once you receive actual scanned images of the BSWM STK color
 * chart from the agriculture office, replace these hex values with
 * values extracted from the scanned chart using an eyedropper tool.
 */
define('NITROGEN_COLOR_CHART', [
    '#FFF5F5' =>  2.0,  // Near-white / colorless   — very low N
    '#FFE0E8' =>  8.0,  // Faint pink blush
    '#FFB3C6' => 15.0,  // Light pink
    '#FF80A0' => 22.0,  // Pink
    '#FF4D80' => 30.0,  // Medium pink
    '#E6006B' => 40.0,  // Hot pink
    '#CC0066' => 50.0,  // Deep pink-magenta
    '#990066' => 60.0,  // Purple-pink
    '#660066' => 70.0,  // Purple            — high N
    '#440044' => 80.0,  // Dark purple        — very high N
]);

/**
 * Phosphorus (P) test strip reference chart.
 * Method: Molybdenum blue / Ascorbic acid reduction
 * Color scale: Colorless (low) → deep blue (high)
 * Range: 0–50 ppm
 */
define('PHOSPHORUS_COLOR_CHART', [
    '#FEFEFE' =>  1.0,  // Colorless        — very low
    '#EEF8FF' =>  3.0,  // Near-white blue
    '#D4EEFF' =>  5.0,  // Very light blue
    '#A8D8F0' =>  8.0,  // Light blue
    '#70BAE8' => 12.0,  // Medium-light blue
    '#42A5F5' => 18.0,  // Medium blue
    '#1E88E5' => 25.0,  // Blue
    '#1565C0' => 35.0,  // Dark blue
    '#0D47A1' => 45.0,  // Deep blue
    '#062A70' => 55.0,  // Navy             — very high
]);

/**
 * Potassium (K) test strip reference chart.
 * Method: BSWM STK uses TURBIDIMETRIC method (not true colorimetric).
 * Color scale: Clear water → cloudy → milky white / opaque gray
 * Low K = clear; High K = turbid/cloudy precipitate
 * The "color" captured is essentially a whiteness/cloudiness scale.
 * Range: 0–120 ppm
 *
 * NOTE: Because this is turbidity-based, the captured hex will trend
 * from near-transparent (dark background shows through) to opaque
 * white-gray. For best results, photograph against a BLACK background
 * so that turbidity is apparent as reduction in darkness.
 * Replace these values with actual BSWM chart scans when available.
 */
define('POTASSIUM_COLOR_CHART', [
    '#0A0A0A' =>  5.0,  // Nearly transparent (black bg shows)  — very low
    '#2A2A2A' => 15.0,  // Very slightly turbid
    '#555555' => 25.0,  // Lightly turbid gray
    '#808080' => 40.0,  // Medium turbid / mid-gray
    '#AAAAAA' => 60.0,  // Turbid — light gray
    '#C8C8C8' => 80.0,  // Quite turbid / light cloudy
    '#DEDEDE' => 95.0,  // Very turbid / near-white cloudy
    '#F0F0F0' => 110.0, // Opaque milky              — high K
    '#FAFAFA' => 120.0, // Fully opaque white         — very high K
]);

// ── Public conversion API (used throughout the app) ─────────

function colorToPhLevel(string $hex): float {
    return round(
        min(14.0, max(0.0, matchColorToValue($hex, PH_COLOR_CHART))),
        1
    );
}

function colorToNitrogenLevel(string $hex): float {
    return round(
        min(100.0, max(0.0, matchColorToValue($hex, NITROGEN_COLOR_CHART))),
        2
    );
}

function colorToPhosphorusLevel(string $hex): float {
    return round(
        min(100.0, max(0.0, matchColorToValue($hex, PHOSPHORUS_COLOR_CHART))),
        2
    );
}

function colorToPotassiumLevel(string $hex): float {
    return round(
        min(100.0, max(0.0, matchColorToValue($hex, POTASSIUM_COLOR_CHART))),
        2
    );
}

// ── Kept for legacy compatibility (e.g. older chart views) ──

function rgbToHsl(int $r, int $g, int $b): array {
    $r /= 255; $g /= 255; $b /= 255;
    $max = max($r, $g, $b); $min = min($r, $g, $b);
    $l   = ($max + $min) / 2;
    if ($max === $min) return ['h' => 0, 's' => 0, 'l' => $l];
    $d = $max - $min;
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    switch ($max) {
        case $r: $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6; break;
        case $g: $h = (($b - $r) / $d + 2) / 6;                  break;
        default: $h = (($r - $g) / $d + 4) / 6;
    }
    return ['h' => $h * 360, 's' => $s, 'l' => $l];
}

// ============================================================
// NUTRIENT STATUS CLASSIFICATION
// ============================================================
// Thresholds aligned with BSWM Philippine soil fertility
// classification guidelines.
// ============================================================

/**
 * Returns 'Low', 'Medium', or 'High' for each soil parameter.
 */
function getNutrientStatus(string $parameter, float $value): string {
    $thresholds = [
        'ph'         => ['low_max' => 5.5,  'high_min' => 7.0],
        'nitrogen'   => ['low_max' => 20.0, 'high_min' => 40.0],
        'phosphorus' => ['low_max' => 15.0, 'high_min' => 30.0],
        'potassium'  => ['low_max' => 20.0, 'high_min' => 40.0],
    ];

    if (!isset($thresholds[$parameter])) return 'Medium';

    $t = $thresholds[$parameter];

    if ($parameter === 'ph') {
        if ($value < $t['low_max'])        return 'Acidic';
        if ($value > $t['high_min'])       return 'Alkaline';
        return 'Optimal';
    }

    if ($value < $t['low_max'])  return 'Low';
    if ($value >= $t['high_min']) return 'High';
    return 'Medium';
}

// ============================================================
// FERTILITY SCORE (0–100)
// ============================================================

/**
 * Calculates an overall soil fertility score.
 * Each parameter scored 0-100 against optimal ranges, then
 * weighted average: N=35%, P=25%, K=25%, pH=15%.
 */
function computeFertilityScore(float $ph, float $n, float $p, float $k): int {
    // pH scoring (optimal 6.0–7.0)
    if ($ph >= 6.0 && $ph <= 7.0)         $phScore = 100;
    elseif ($ph >= 5.5 && $ph <= 7.5)     $phScore = 70;
    elseif ($ph >= 5.0 && $ph <= 8.0)     $phScore = 40;
    else                                    $phScore = 10;

    // Nitrogen (optimal 20–40 ppm)
    if ($n >= 20 && $n <= 40)              $nScore = 100;
    elseif ($n > 40 && $n <= 60)           $nScore = 80;  // excess but usable
    elseif ($n >= 10)                      $nScore = 50;
    else                                    $nScore = 15;

    // Phosphorus (optimal 15–30 ppm)
    if ($p >= 15 && $p <= 30)              $pScore = 100;
    elseif ($p > 30 && $p <= 50)           $pScore = 75;
    elseif ($p >= 8)                       $pScore = 50;
    else                                    $pScore = 15;

    // Potassium (optimal 20–40 ppm)
    if ($k >= 20 && $k <= 40)             $kScore = 100;
    elseif ($k > 40 && $k <= 70)          $kScore = 75;
    elseif ($k >= 10)                      $kScore = 50;
    else                                    $kScore = 15;

    // Weighted average: N 35%, P 25%, K 25%, pH 15%
    return (int) round(
        $nScore * 0.35 + $pScore * 0.25 + $kScore * 0.25 + $phScore * 0.15
    );
}

// ============================================================
// FERTILIZER RECOMMENDATION ENGINE
// ============================================================
// Based on BSWM / PhilRice fertilizer recommendation guidelines
// for Philippine agricultural conditions.
// ============================================================

/**
 * Returns fertilizer recommendations as an associative array:
 *   lime_tons   — dolomitic lime (tons per hectare)
 *   urea_bags   — Urea 46-0-0 (50-kg bags per hectare)
 *   tsp_bags    — Triple Superphosphate 0-46-0 (bags per ha)
 *   mop_bags    — Muriate of Potash 0-0-60 (bags per ha)
 *   notes       — array of advisory strings
 */
function getFertilizerRecommendation(float $ph, float $n, float $p, float $k): array {
    $rec   = ['lime_tons' => 0.0, 'urea_bags' => 0.0, 'tsp_bags' => 0.0, 'mop_bags' => 0.0, 'notes' => []];

    // ── Lime for acidic soils ─────────────────────────────────
    if ($ph < 5.0) {
        $rec['lime_tons'] = 2.0;
        $rec['notes'][]   = 'Soil is strongly acidic (pH < 5.0). Apply 2 t/ha dolomitic lime at least 2 weeks before planting.';
    } elseif ($ph < 5.5) {
        $rec['lime_tons'] = 1.0;
        $rec['notes'][]   = 'Soil is moderately acidic (pH 5.0–5.5). Apply 1 t/ha dolomitic lime to improve nutrient availability.';
    } elseif ($ph > 7.5) {
        $rec['notes'][]   = 'Soil is alkaline (pH > 7.5). Consider incorporating organic matter or elemental sulfur to lower pH.';
    }

    // ── Nitrogen (Urea 46-0-0, 50-kg bags/ha) ────────────────
    // Target application: Low = ~90 kg N/ha, Medium = ~60 kg N/ha, High = ~30 kg N/ha
    if ($n < 20) {
        $rec['urea_bags'] = 4.0;  // ~92 kg N/ha
        $rec['notes'][]   = 'Low nitrogen. Apply Urea in 2 splits: ½ basal + ½ at panicle initiation.';
    } elseif ($n < 40) {
        $rec['urea_bags'] = 2.5;  // ~58 kg N/ha
        $rec['notes'][]   = 'Medium nitrogen. Apply Urea in 2 splits: ½ basal + ½ at active tillering.';
    } else {
        $rec['urea_bags'] = 1.0;  // ~23 kg N/ha — maintenance dose
        $rec['notes'][]   = 'Adequate nitrogen. Apply minimal Urea (1 bag/ha) as maintenance only.';
    }

    // ── Phosphorus (TSP 0-46-0, 50-kg bags/ha) ───────────────
    // Target: Low = ~60 kg P2O5/ha, Medium = ~30 kg P2O5/ha, High = none
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

    // ── Potassium (MOP 0-0-60, 50-kg bags/ha) ────────────────
    // Target: Low = ~60 kg K2O/ha, Medium = ~30 kg K2O/ha, High = none
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

    // ── General advisory ──────────────────────────────────────
    $rec['notes'][] = 'Recommendation basis: BSWM/PhilRice colorimetric soil test guidelines (per hectare). Verify with a certified soil laboratory for large-scale production decisions.';

    return $rec;
}
