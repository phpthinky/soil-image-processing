<?php
// ============================================================
// config.php — Core configuration, DB connection, helpers
// ============================================================

session_start();

// ── Database credentials ────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'soil_analyzer');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── PDO connection ──────────────────────────────────────────
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
    // Show a friendly error in production; log the real message
    error_log('DB connection failed: ' . $e->getMessage());
    die('<h3 style="color:red;font-family:sans-serif;">Database connection failed. Please contact the administrator.</h3>');
}

// ── Auth helpers ────────────────────────────────────────────

/**
 * Returns true if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Returns true if the logged-in user has the 'admin' role.
 */
function isAdmin(): bool {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Redirects to the given URL and stops execution.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ── Color ↔ Soil-parameter conversion helpers ───────────────

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
 * Converts RGB (0-255 each) to HSL.
 * Returns ['h' => 0-360, 's' => 0-1, 'l' => 0-1].
 */
function rgbToHsl(int $r, int $g, int $b): array {
    $r /= 255; $g /= 255; $b /= 255;
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $l   = ($max + $min) / 2;

    if ($max === $min) {
        return ['h' => 0, 's' => 0, 'l' => $l];
    }

    $d = $max - $min;
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

    switch ($max) {
        case $r: $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6; break;
        case $g: $h = (($b - $r) / $d + 2) / 6;                  break;
        default: $h = (($r - $g) / $d + 4) / 6;
    }

    return ['h' => $h * 360, 's' => $s, 'l' => $l];
}

/**
 * Estimates soil pH (0–14) from the color of a pH indicator strip.
 *
 * pH test strips change colour on a red→orange→yellow→green→blue scale:
 *   Hue  0–30°  → pH 4.0–5.0  (red / strongly acidic)
 *   Hue 30–60°  → pH 5.0–6.0  (orange)
 *   Hue 60–90°  → pH 6.0–7.0  (yellow-green / neutral)
 *   Hue 90–150° → pH 7.0–7.5  (green / slightly alkaline)
 *   Hue 150–240°→ pH 7.5–8.5  (blue-green to blue)
 *   Hue 240–360°→ pH 8.5–9.0  (blue-purple / strongly alkaline)
 */
function colorToPhLevel(string $hex): float {
    $rgb = hexToRgb($hex);
    $hsl = rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    $h   = $hsl['h'];

    if ($h <= 30)        return round(4.0 + ($h / 30) * 1.0, 1);
    if ($h <= 60)        return round(5.0 + (($h - 30)  / 30)  * 1.0, 1);
    if ($h <= 90)        return round(6.0 + (($h - 60)  / 30)  * 1.0, 1);
    if ($h <= 150)       return round(7.0 + (($h - 90)  / 60)  * 0.5, 1);
    if ($h <= 240)       return round(7.5 + (($h - 150) / 90)  * 1.0, 1);
    return               round(8.5 + (($h - 240) / 120) * 0.5, 1);
}

/**
 * Estimates Nitrogen content (ppm) from indicator-strip colour.
 * Light yellow = low N; dark green = high N.
 * Range: 0–100 ppm.
 */
function colorToNitrogenLevel(string $hex): float {
    $rgb = hexToRgb($hex);
    $hsl = rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    // Green dominance × darkness gives an N score 0–1
    $greenDominance = max(0, ($rgb['g'] - max($rgb['r'], $rgb['b'])) / 255);
    $darkness       = 1 - $hsl['l'];
    $score          = ($greenDominance * 0.65 + $darkness * 0.35);
    return round(min(100, max(0, $score * 100)), 2);
}

/**
 * Estimates Phosphorus content (ppm) from indicator-strip colour.
 * Yellow/orange = low P; blue = high P.
 * Range: 0–100 ppm.
 */
function colorToPhosphorusLevel(string $hex): float {
    $rgb = hexToRgb($hex);
    $hsl = rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    $h   = $hsl['h'];

    // Blue hues (200–260°) indicate high phosphorus
    if ($h >= 200 && $h <= 260) {
        $score = 0.5 + (($h - 200) / 60) * 0.5;
    } elseif ($h < 60 || $h > 300) {
        $score = 0.05; // red/orange → very low
    } else {
        $score = 0.25; // mid range
    }
    return round(min(100, max(0, $score * 100)), 2);
}

/**
 * Estimates Potassium content (ppm) from indicator-strip colour.
 * Pale/white = low K; orange/purple = high K.
 * Range: 0–100 ppm.
 */
function colorToPotassiumLevel(string $hex): float {
    $rgb = hexToRgb($hex);
    $hsl = rgbToHsl($rgb['r'], $rgb['g'], $rgb['b']);
    $h   = $hsl['h'];
    $s   = $hsl['s'];

    // Warm and purple hues indicate higher potassium
    $warmth = ($h <= 60 || $h >= 300) ? 1.0 : (($h >= 240 && $h < 300) ? ($h - 240) / 60 : 0.1);
    $score  = ($warmth * 0.55 + $s * 0.45);
    return round(min(100, max(0, $score * 100)), 2);
}

/**
 * Calculates an overall fertility score (0–100) from NPK + pH values.
 *
 * Each parameter is scored against a generalised optimal range, then
 * the four scores are averaged.
 */
function computeFertilityScore(float $ph, float $n, float $p, float $k): int {
    // pH: optimal 6.0–7.0
    if ($ph >= 6.0 && $ph <= 7.0)       $phScore = 100;
    elseif ($ph >= 5.5 || $ph <= 7.5)   $phScore = 70;
    elseif ($ph >= 5.0 || $ph <= 8.0)   $phScore = 40;
    else                                  $phScore = 10;

    // Nitrogen: optimal 20–60 ppm
    if ($n >= 20 && $n <= 60)            $nScore = 100;
    elseif ($n >= 10)                    $nScore = 60;
    else                                  $nScore = 20;

    // Phosphorus: optimal 15–40 ppm
    if ($p >= 15 && $p <= 40)            $pScore = 100;
    elseif ($p >= 8)                     $pScore = 60;
    else                                  $pScore = 20;

    // Potassium: optimal 20–50 ppm
    if ($k >= 20 && $k <= 50)            $kScore = 100;
    elseif ($k >= 10)                    $kScore = 60;
    else                                  $kScore = 20;

    return (int) round(($phScore + $nScore + $pScore + $kScore) / 4);
}
