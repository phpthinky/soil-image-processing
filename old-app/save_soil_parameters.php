<?php
// ============================================================
// save_soil_parameters.php — API endpoint: capture one test
// reading per parameter (up to 3 per parameter per sample).
// After all 3 readings, the averaged color is written back to
// soil_samples.{param}_color_hex so downstream analysis works.
// ============================================================
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// ── Validate required fields ─────────────────────────────────
$required = ['sample_id', 'parameter', 'color_hex', 'r', 'g', 'b', 'test_number'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

$sample_id   = intval($input['sample_id']);
$parameter   = $input['parameter'];
$color_hex   = strtoupper($input['color_hex']);
$r           = intval($input['r']);
$g           = intval($input['g']);
$b           = intval($input['b']);
$test_number = intval($input['test_number']);

$valid_params = ['ph', 'nitrogen', 'phosphorus', 'potassium'];
if (!in_array($parameter, $valid_params)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameter']);
    exit;
}

if (!preg_match('/^#[0-9A-F]{6}$/i', $color_hex)) {
    echo json_encode(['success' => false, 'message' => 'Invalid color_hex format']);
    exit;
}

if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
    echo json_encode(['success' => false, 'message' => 'Invalid RGB values (0-255 expected)']);
    exit;
}

if ($test_number < 1 || $test_number > 3) {
    echo json_encode(['success' => false, 'message' => 'test_number must be 1, 2, or 3']);
    exit;
}

try {
    // ── Verify sample ownership ───────────────────────────────
    $stmt = $pdo->prepare("SELECT id, user_id FROM soil_samples WHERE id = ?");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch();

    if (!$sample) {
        echo json_encode(['success' => false, 'message' => 'Sample not found']);
        exit;
    }

    if ($sample['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Permission denied']);
        exit;
    }

    // ── Compute the value for this individual reading ─────────
    $computed_value = match($parameter) {
        'ph'         => colorToPhLevel($color_hex),
        'nitrogen'   => colorToNitrogenLevel($color_hex),
        'phosphorus' => colorToPhosphorusLevel($color_hex),
        'potassium'  => colorToPotassiumLevel($color_hex),
    };

    // ── Upsert the individual reading ─────────────────────────
    $pdo->prepare(
        "INSERT INTO soil_color_readings
             (sample_id, parameter, test_number, color_hex, r, g, b, computed_value)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
             color_hex = VALUES(color_hex),
             r = VALUES(r), g = VALUES(g), b = VALUES(b),
             computed_value = VALUES(computed_value),
             captured_at = NOW()"
    )->execute([$sample_id, $parameter, $test_number, $color_hex, $r, $g, $b, $computed_value]);

    // ── Count how many tests are done for this parameter ──────
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) as cnt,
                AVG(r) as avg_r, AVG(g) as avg_g, AVG(b) as avg_b
           FROM soil_color_readings
          WHERE sample_id = ? AND parameter = ?"
    );
    $countStmt->execute([$sample_id, $parameter]);
    $agg = $countStmt->fetch();
    $tests_done = (int) $agg['cnt'];

    // ── After all 3 tests: write averaged color to soil_samples ─
    $avg_hex = null;
    if ($tests_done === 3) {
        $avg_r   = (int) round($agg['avg_r']);
        $avg_g   = (int) round($agg['avg_g']);
        $avg_b   = (int) round($agg['avg_b']);
        $avg_hex = sprintf('#%02X%02X%02X', $avg_r, $avg_g, $avg_b);

        $col = $parameter . '_color_hex';
        $pdo->prepare(
            "UPDATE soil_samples SET $col = ? WHERE id = ?"
        )->execute([$avg_hex, $sample_id]);
    }

    // ── Count total test readings across all 4 parameters ─────
    $totalStmt = $pdo->prepare(
        "SELECT COUNT(*) as total FROM soil_color_readings WHERE sample_id = ?"
    );
    $totalStmt->execute([$sample_id]);
    $total_readings = (int) $totalStmt->fetch()['total'];

    // Update tests_completed progress counter
    $pdo->prepare(
        "UPDATE soil_samples SET tests_completed = ? WHERE id = ?"
    )->execute([$total_readings, $sample_id]);

    echo json_encode([
        'success'        => true,
        'message'        => ucfirst($parameter) . " test $test_number saved",
        'parameter'      => $parameter,
        'test_number'    => $test_number,
        'tests_done'     => $tests_done,
        'computed_value' => $computed_value,
        'avg_hex'        => $avg_hex,          // non-null only after 3rd test
        'total_readings' => $total_readings,   // 0-12
    ]);

} catch (PDOException $e) {
    error_log("DB error in save_soil_parameters.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
