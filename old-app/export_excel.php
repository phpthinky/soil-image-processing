<?php
// ============================================================
// export_excel.php — Export soil analysis results to CSV
// (designed for Phase 2 system import)
// ============================================================
require_once 'config.php';

if (!isLoggedIn()) redirect('login.php');

$sample_id = isset($_GET['sample_id']) ? intval($_GET['sample_id']) : 0;

// ── Fetch data ───────────────────────────────────────────────
if ($sample_id > 0) {
    // Single sample export
    if (isAdmin()) {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username, u.email
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              WHERE s.id = ?"
        );
        $stmt->execute([$sample_id]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username, u.email
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              WHERE s.id = ? AND s.user_id = ?"
        );
        $stmt->execute([$sample_id, $_SESSION['user_id']]);
    }
    $samples = $stmt->fetchAll();

    if (empty($samples)) {
        $_SESSION['error_message'] = 'Sample not found or access denied.';
        redirect('results.php');
    }

    // Include individual test readings for the single sample
    $readingsStmt = $pdo->prepare(
        "SELECT parameter, test_number, color_hex, computed_value
           FROM soil_color_readings
          WHERE sample_id = ?
          ORDER BY parameter, test_number"
    );
    $readingsStmt->execute([$sample_id]);
    $allReadings = $readingsStmt->fetchAll();

    // Organise readings: $readings['ph'][1] = value, etc.
    $readings = [];
    foreach ($allReadings as $row) {
        $readings[$row['parameter']][$row['test_number']] = [
            'hex'   => $row['color_hex'],
            'value' => $row['computed_value'],
        ];
    }

    $filename = 'soil_sample_' . $sample_id . '_' . date('Ymd_His') . '.csv';
    $exportMode = 'single';
} else {
    // All samples export (admin or own)
    if (isAdmin()) {
        $samples = $pdo->query(
            "SELECT s.*, u.username, u.email
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              ORDER BY s.created_at DESC"
        )->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username, u.email
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              WHERE s.user_id = ?
              ORDER BY s.created_at DESC"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $samples = $stmt->fetchAll();
    }
    $filename    = 'soil_samples_export_' . date('Ymd_His') . '.csv';
    $exportMode  = 'all';
    $readings    = [];
}

// ── Compute fertilizer recommendations for each analyzed sample ─
$fertRecs = [];
foreach ($samples as $s) {
    if (!is_null($s['ph_level'])) {
        $fertRecs[$s['id']] = getFertilizerRecommendation(
            (float)$s['ph_level'],
            (float)$s['nitrogen_level'],
            (float)$s['phosphorus_level'],
            (float)$s['potassium_level']
        );
    }
}

// ── Output CSV ───────────────────────────────────────────────
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
// UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// ── SECTION 1: SAMPLE INFORMATION ───────────────────────────
fputcsv($out, ['=== SOIL ANALYSIS REPORT ===']);
fputcsv($out, ['Export Date', date('F j, Y g:i A')]);
fputcsv($out, ['System', 'Soil Fertility Analyzer — Office of the Municipal Agriculturist']);
fputcsv($out, ['Phase 2 Import Version', '1.0']);
fputcsv($out, []);

// ── SECTION 2: MAIN DATA ─────────────────────────────────────
// Headers — Phase 2 import expects these exact column names
$headers = [
    // Identifiers
    'sample_id',
    'sample_name',
    'farmer_name',
    'address',
    'farm_location',
    'date_received',
    'date_tested',
    'analyzed_at',
    'submitted_by',
    // Soil readings
    'ph_level',
    'nitrogen_ppm',
    'phosphorus_ppm',
    'potassium_ppm',
    'fertility_score',
    // Status flags
    'ph_status',
    'nitrogen_status',
    'phosphorus_status',
    'potassium_status',
    // Averaged color hex
    'ph_color_hex',
    'nitrogen_color_hex',
    'phosphorus_color_hex',
    'potassium_color_hex',
    // 3-test individual readings
    'ph_test1_hex', 'ph_test1_value',
    'ph_test2_hex', 'ph_test2_value',
    'ph_test3_hex', 'ph_test3_value',
    'n_test1_hex',  'n_test1_value',
    'n_test2_hex',  'n_test2_value',
    'n_test3_hex',  'n_test3_value',
    'p_test1_hex',  'p_test1_value',
    'p_test2_hex',  'p_test2_value',
    'p_test3_hex',  'p_test3_value',
    'k_test1_hex',  'k_test1_value',
    'k_test2_hex',  'k_test2_value',
    'k_test3_hex',  'k_test3_value',
    // Fertilizer recommendations
    'fert_lime_tons_per_ha',
    'fert_urea_bags_per_ha',
    'fert_tsp_bags_per_ha',
    'fert_mop_bags_per_ha',
    // Crop recommendation
    'recommended_crop',
    // AI recommendation
    'ai_recommendation',
];
fputcsv($out, $headers);

foreach ($samples as $s) {
    $sid  = $s['id'];
    $fr   = $fertRecs[$sid] ?? null;
    $rd   = ($exportMode === 'single') ? $readings : [];

    // For all-export mode, load readings per-sample
    if ($exportMode === 'all' && !is_null($s['ph_level'])) {
        $rs = $pdo->prepare(
            "SELECT parameter, test_number, color_hex, computed_value
               FROM soil_color_readings WHERE sample_id = ?
               ORDER BY parameter, test_number"
        );
        $rs->execute([$sid]);
        foreach ($rs->fetchAll() as $row) {
            $rd[$row['parameter']][$row['test_number']] = [
                'hex'   => $row['color_hex'],
                'value' => $row['computed_value'],
            ];
        }
    }

    // Helper to safely get reading
    $rv = fn($param, $num, $field) => $rd[$param][$num][$field] ?? '';

    $row = [
        $sid,
        $s['sample_name'],
        $s['farmer_name'],
        $s['address'],
        $s['location']    ?? '',
        $s['sample_date'],
        $s['date_tested'],
        $s['analyzed_at'] ?? '',
        $s['username'],
        // Soil values
        $s['ph_level']         ?? '',
        $s['nitrogen_level']   ?? '',
        $s['phosphorus_level'] ?? '',
        $s['potassium_level']  ?? '',
        $s['fertility_score']  ?? '',
        // Status
        (!is_null($s['ph_level']))         ? getNutrientStatus('ph',         (float)$s['ph_level'])         : '',
        (!is_null($s['nitrogen_level']))   ? getNutrientStatus('nitrogen',   (float)$s['nitrogen_level'])   : '',
        (!is_null($s['phosphorus_level'])) ? getNutrientStatus('phosphorus', (float)$s['phosphorus_level']) : '',
        (!is_null($s['potassium_level']))  ? getNutrientStatus('potassium',  (float)$s['potassium_level'])  : '',
        // Averaged hex
        $s['ph_color_hex']         ?? '',
        $s['nitrogen_color_hex']   ?? '',
        $s['phosphorus_color_hex'] ?? '',
        $s['potassium_color_hex']  ?? '',
        // 3-test readings
        $rv('ph', 1, 'hex'),  $rv('ph', 1, 'value'),
        $rv('ph', 2, 'hex'),  $rv('ph', 2, 'value'),
        $rv('ph', 3, 'hex'),  $rv('ph', 3, 'value'),
        $rv('nitrogen', 1, 'hex'),  $rv('nitrogen', 1, 'value'),
        $rv('nitrogen', 2, 'hex'),  $rv('nitrogen', 2, 'value'),
        $rv('nitrogen', 3, 'hex'),  $rv('nitrogen', 3, 'value'),
        $rv('phosphorus', 1, 'hex'),  $rv('phosphorus', 1, 'value'),
        $rv('phosphorus', 2, 'hex'),  $rv('phosphorus', 2, 'value'),
        $rv('phosphorus', 3, 'hex'),  $rv('phosphorus', 3, 'value'),
        $rv('potassium', 1, 'hex'),  $rv('potassium', 1, 'value'),
        $rv('potassium', 2, 'hex'),  $rv('potassium', 2, 'value'),
        $rv('potassium', 3, 'hex'),  $rv('potassium', 3, 'value'),
        // Fertilizer
        $fr['lime_tons']  ?? '',
        $fr['urea_bags']  ?? '',
        $fr['tsp_bags']   ?? '',
        $fr['mop_bags']   ?? '',
        // Crop & AI
        $s['recommended_crop']     ?? '',
        $s['ai_recommendation']    ?? '',
    ];

    fputcsv($out, $row);
}

// ── SECTION 3: LEGEND ────────────────────────────────────────
fputcsv($out, []);
fputcsv($out, ['=== COLUMN LEGEND ===']);
fputcsv($out, ['Column', 'Description', 'Units / Notes']);
$legend = [
    ['ph_level',              'Soil acidity/alkalinity',                '0-14 scale'],
    ['nitrogen_ppm',          'Available nitrogen (NO3-N)',              'ppm (mg/kg)'],
    ['phosphorus_ppm',        'Available phosphorus (Bray P1)',          'ppm (mg/kg)'],
    ['potassium_ppm',         'Exchangeable potassium',                  'ppm (mg/kg)'],
    ['fertility_score',       'Overall fertility index',                 '0-100 (weighted N35% P25% K25% pH15%)'],
    ['ph_status',             'pH classification',                       'Acidic / Optimal / Alkaline'],
    ['nitrogen_status',       'Nitrogen fertility class',                'Low (<20) / Medium (20-40) / High (>40)'],
    ['phosphorus_status',     'Phosphorus fertility class',              'Low (<15) / Medium (15-30) / High (>30)'],
    ['potassium_status',      'Potassium fertility class',               'Low (<20) / Medium (20-40) / High (>40)'],
    ['fert_lime_tons_per_ha', 'Dolomitic lime application rate',        'tons/ha (apply 2 weeks before planting)'],
    ['fert_urea_bags_per_ha', 'Urea (46-0-0) application rate',        '50-kg bags/ha'],
    ['fert_tsp_bags_per_ha',  'Triple Superphosphate (0-46-0) rate',   '50-kg bags/ha'],
    ['fert_mop_bags_per_ha',  'Muriate of Potash (0-0-60) rate',       '50-kg bags/ha'],
    ['*_test1/2/3_value',     'Individual reading from each of 3 tests','Same unit as averaged value'],
    ['*_color_hex',           'Averaged color from 3 webcam captures',  'CSS hex #RRGGBB'],
];
foreach ($legend as $row) fputcsv($out, $row);

fputcsv($out, []);
fputcsv($out, ['=== METHOD NOTES ===']);
fputcsv($out, ['Color Analysis', 'CIE L*a*b* color space + Delta-E inverse-distance weighted interpolation against BSWM reference chart']);
fputcsv($out, ['Accuracy',       '3-test average reduces single-frame webcam error by ~60%']);
fputcsv($out, ['Reference',      'BSWM / PhilRice Soil Test Kit colorimetric guidelines, Philippines']);
fputcsv($out, ['Fertilizer',     'BSWM fertilizer recommendation guidelines per hectare (50-kg bag basis)']);

fclose($out);
exit;
