<?php
// ============================================================
// results.php — Soil sample results, 3-test webcam capture,
//               crop recommendations, fertilizer advice, AI
// ============================================================
require_once 'config.php';

if (!isLoggedIn()) redirect('login.php');

// ── Redo: reset all readings for this sample ────────────────
if (isset($_GET['redo'], $_GET['sample_id']) && $_GET['redo'] == 1) {
    $sid = intval($_GET['sample_id']);
    // Verify ownership
    $chk = $pdo->prepare("SELECT user_id FROM soil_samples WHERE id = ?");
    $chk->execute([$sid]);
    $row = $chk->fetch();
    if ($row && ($row['user_id'] == $_SESSION['user_id'] || isAdmin())) {
        $pdo->prepare("DELETE FROM soil_color_readings WHERE sample_id = ?")->execute([$sid]);
        $pdo->prepare(
            "UPDATE soil_samples
                SET ph_color_hex=NULL, nitrogen_color_hex=NULL,
                    phosphorus_color_hex=NULL, potassium_color_hex=NULL,
                    ph_level=NULL, nitrogen_level=NULL,
                    phosphorus_level=NULL, potassium_level=NULL,
                    fertility_score=NULL, analyzed_at=NULL,
                    ai_recommendation=NULL, recommended_crop=NULL,
                    tests_completed=0
              WHERE id = ?"
        )->execute([$sid]);
    }
    redirect("results.php?sample_id=$sid");
}

// ── Fetch sample or list ─────────────────────────────────────
$sample    = null;
$sample_id = isset($_GET['sample_id']) ? intval($_GET['sample_id']) : 0;

if ($sample_id > 0) {
    if (isAdmin()) {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username FROM soil_samples s
               JOIN users u ON s.user_id = u.id WHERE s.id = ?"
        );
        $stmt->execute([$sample_id]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username FROM soil_samples s
               JOIN users u ON s.user_id = u.id WHERE s.id = ? AND s.user_id = ?"
        );
        $stmt->execute([$sample_id, $_SESSION['user_id']]);
    }
    $sample = $stmt->fetch();

    if (!$sample) {
        $_SESSION['error_message'] = 'Sample not found or access denied.';
        redirect('results.php');
    }

    // ── Load individual test readings ────────────────────────
    $readingsStmt = $pdo->prepare(
        "SELECT parameter, test_number, color_hex, r, g, b, computed_value
           FROM soil_color_readings
          WHERE sample_id = ?
          ORDER BY parameter, test_number"
    );
    $readingsStmt->execute([$sample_id]);
    $allReadings = $readingsStmt->fetchAll();

    // Organise: $readings['ph'][1] = [...], $readings['ph'][2] = [...] etc.
    $readings = ['ph' => [], 'nitrogen' => [], 'phosphorus' => [], 'potassium' => []];
    foreach ($allReadings as $row) {
        $readings[$row['parameter']][$row['test_number']] = $row;
    }

    // ── Auto-compute when all 4 averaged colors are present ──
    $allCaptured = $sample['ph_color_hex'] && $sample['nitrogen_color_hex']
                && $sample['phosphorus_color_hex'] && $sample['potassium_color_hex'];

    if ($allCaptured && is_null($sample['ph_level'])) {
        $ph = colorToPhLevel($sample['ph_color_hex']);
        $n  = colorToNitrogenLevel($sample['nitrogen_color_hex']);
        $p  = colorToPhosphorusLevel($sample['phosphorus_color_hex']);
        $k  = colorToPotassiumLevel($sample['potassium_color_hex']);
        $fs = computeFertilityScore($ph, $n, $p, $k);

        // Find top recommended crop
        $topCropStmt = $pdo->prepare(
            "SELECT name,
               (CASE WHEN :ph BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
                CASE WHEN :n  BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
                CASE WHEN :p  BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
                CASE WHEN :k  BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END)
               AS score
             FROM crops
             ORDER BY score DESC, name ASC
             LIMIT 1"
        );
        $topCropStmt->execute([':ph' => $ph, ':n' => $n, ':p' => $p, ':k' => $k]);
        $topCrop = $topCropStmt->fetch();

        $pdo->prepare(
            "UPDATE soil_samples
                SET ph_level=?, nitrogen_level=?, phosphorus_level=?,
                    potassium_level=?, fertility_score=?,
                    recommended_crop=?, analyzed_at=NOW()
              WHERE id=?"
        )->execute([$ph, $n, $p, $k, $fs, $topCrop['name'] ?? null, $sample_id]);

        redirect("results.php?sample_id=$sample_id");
    }

    // ── Crop recommendations ─────────────────────────────────
    $recommendations = [];
    if (!is_null($sample['ph_level'])) {
        $recStmt = $pdo->prepare(
            "SELECT *, (
                  CASE WHEN :ph BETWEEN min_ph         AND max_ph         THEN 1 ELSE 0 END +
                  CASE WHEN :n  BETWEEN min_nitrogen    AND max_nitrogen    THEN 1 ELSE 0 END +
                  CASE WHEN :p  BETWEEN min_phosphorus  AND max_phosphorus  THEN 1 ELSE 0 END +
                  CASE WHEN :k  BETWEEN min_potassium   AND max_potassium   THEN 1 ELSE 0 END
             ) AS match_score
               FROM crops
              WHERE min_ph <= :ph2 AND max_ph >= :ph3
              ORDER BY match_score DESC, name ASC"
        );
        $recStmt->execute([
            ':ph' => $sample['ph_level'], ':n'  => $sample['nitrogen_level'],
            ':p'  => $sample['phosphorus_level'], ':k'  => $sample['potassium_level'],
            ':ph2' => $sample['ph_level'], ':ph3' => $sample['ph_level'],
        ]);
        $recommendations = $recStmt->fetchAll();

        // Fertilizer recommendation
        $fertRec = getFertilizerRecommendation(
            (float)$sample['ph_level'],
            (float)$sample['nitrogen_level'],
            (float)$sample['phosphorus_level'],
            (float)$sample['potassium_level']
        );
    }

} else {
    // ── List view ────────────────────────────────────────────
    if (isAdmin()) {
        $samples = $pdo->query(
            "SELECT s.*, u.username FROM soil_samples s
               JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC"
        )->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM soil_samples WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $samples = $stmt->fetchAll();
    }
}
?>

<?php include 'includes/header.php'; ?>

<?php if ($sample_id > 0 && $sample): ?>
<!-- ============================================================
     DETAIL VIEW
     ============================================================ -->

<div class="row mb-3">
    <div class="col">
        <a href="results.php" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Samples
        </a>
        <?php if (!is_null($sample['ph_level'])): ?>
        <a href="export_excel.php?sample_id=<?= $sample_id ?>"
           class="btn btn-sm btn-success ms-2">
            <i class="fas fa-file-excel"></i> Export to Excel
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Sample info card ─────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-vial me-2"></i>
            <?= htmlspecialchars($sample['sample_name']) ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <p><strong>Farmer:</strong> <?= htmlspecialchars($sample['farmer_name']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($sample['address']) ?></p>
                <p><strong>Farm Location:</strong> <?= htmlspecialchars($sample['location'] ?? '—') ?></p>
            </div>
            <div class="col-md-4">
                <p><strong>Date Received:</strong> <?= date('F j, Y', strtotime($sample['sample_date'])) ?></p>
                <p><strong>Date Tested:</strong>   <?= date('F j, Y', strtotime($sample['date_tested'])) ?></p>
                <?php if ($sample['analyzed_at']): ?>
                <p><strong>Analyzed:</strong> <?= date('F j, Y g:i A', strtotime($sample['analyzed_at'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-center">
                <?php if (!is_null($sample['fertility_score'])): ?>
                    <?php
                    $fsColor = $sample['fertility_score'] >= 75 ? 'success'
                             : ($sample['fertility_score'] >= 50 ? 'warning' : 'danger');
                    ?>
                    <div class="display-4 fw-bold text-<?= $fsColor ?>">
                        <?= $sample['fertility_score'] ?>%
                    </div>
                    <p class="text-muted mb-0">Fertility Score</p>
                    <?php if ($sample['recommended_crop']): ?>
                    <span class="badge bg-success mt-1">
                        <i class="fas fa-seedling me-1"></i>
                        Top: <?= htmlspecialchars($sample['recommended_crop']) ?>
                    </span>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted"><em>Not yet analyzed</em></p>
                    <small class="text-muted">
                        <?= ($sample['tests_completed'] ?? 0) ?>/12 tests captured
                    </small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     3-TEST WEBCAM CAPTURE SECTION
     (Shown while analysis is incomplete)
     ============================================================ -->
<?php
$params = [
    'ph'         => ['label' => 'Soil pH',       'unit' => '',    'icon' => 'fa-flask'],
    'nitrogen'   => ['label' => 'Nitrogen (N)',   'unit' => 'ppm', 'icon' => 'fa-leaf'],
    'phosphorus' => ['label' => 'Phosphorus (P)', 'unit' => 'ppm', 'icon' => 'fa-atom'],
    'potassium'  => ['label' => 'Potassium (K)',  'unit' => 'ppm', 'icon' => 'fa-seedling'],
];
$fullyAnalyzed = !is_null($sample['ph_level']);
$allAveraged   = $sample['ph_color_hex'] && $sample['nitrogen_color_hex']
              && $sample['phosphorus_color_hex'] && $sample['potassium_color_hex'];
?>
<?php if (!$fullyAnalyzed): ?>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-camera me-2"></i>
            Webcam Capture — 3-Test Accuracy System
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 mb-3">
            <i class="fas fa-info-circle me-1"></i>
            <strong>How it works:</strong> Capture each indicator strip
            <strong>3 times</strong>. The system averages all three readings
            to eliminate single-frame errors. Place the strip squarely inside
            the target circle before each capture.
        </div>

        <div class="row">
            <!-- Webcam feed -->
            <div class="col-md-5 text-center mb-3">
                <div style="position:relative; display:inline-block;">
                    <video id="webcam" width="380" height="280" autoplay playsinline
                           style="border:2px solid #ccc; border-radius:8px;"></video>
                    <div style="
                        position:absolute; top:50%; left:50%;
                        transform:translate(-50%,-50%);
                        width:80px; height:80px;
                        border:3px dashed rgba(255,255,255,0.85);
                        border-radius:50%; pointer-events:none;
                        box-shadow:0 0 0 1px rgba(0,0,0,0.3);">
                    </div>
                </div>
                <canvas id="snapshot" width="380" height="280" style="display:none;"></canvas>
                <br>
                <button id="startCameraBtn" class="btn btn-outline-secondary mt-2"
                        onclick="startCamera()">
                    <i class="fas fa-video"></i> Start Camera
                </button>
            </div>

            <!-- Parameter capture table -->
            <div class="col-md-7">
                <table class="table table-bordered align-middle table-sm" id="captureTable">
                    <thead class="table-success">
                        <tr>
                            <th>Parameter</th>
                            <th class="text-center">Test 1</th>
                            <th class="text-center">Test 2</th>
                            <th class="text-center">Test 3</th>
                            <th class="text-center">Average</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($params as $key => $meta): ?>
                        <?php
                        $r1 = $readings[$key][1] ?? null;
                        $r2 = $readings[$key][2] ?? null;
                        $r3 = $readings[$key][3] ?? null;
                        $avgHex = ($r1 && $r2 && $r3)
                            ? $sample[$key . '_color_hex']
                            : null;
                        ?>
                        <tr id="row-<?= $key ?>">
                            <td>
                                <i class="fas <?= $meta['icon'] ?> me-1 text-success"></i>
                                <strong><?= $meta['label'] ?></strong>
                            </td>
                            <?php for ($t = 1; $t <= 3; $t++):
                                $rd = $readings[$key][$t] ?? null;
                            ?>
                            <td class="text-center p-1">
                                <?php if ($rd): ?>
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <div style="width:36px;height:18px;background:<?= $rd['color_hex'] ?>;
                                                    border:1px solid #ccc;border-radius:3px;"
                                             title="<?= $rd['color_hex'] ?>"></div>
                                        <small class="text-muted" style="font-size:10px;"><?= $rd['color_hex'] ?></small>
                                        <button class="btn btn-outline-secondary btn-sm py-0 px-1"
                                                style="font-size:10px;"
                                                onclick="captureTest('<?= $key ?>', <?= $t ?>)">
                                            Redo
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success"
                                            onclick="captureTest('<?= $key ?>', <?= $t ?>)"
                                            id="btn-<?= $key ?>-<?= $t ?>">
                                        <i class="fas fa-camera"></i> #<?= $t ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                            <?php endfor; ?>
                            <td class="text-center" id="avg-cell-<?= $key ?>">
                                <?php if ($avgHex): ?>
                                    <div style="width:40px;height:20px;background:<?= $avgHex ?>;
                                                border:1px solid #999;border-radius:3px;margin:auto;"></div>
                                    <small class="text-success fw-bold" style="font-size:10px;">
                                        <?= $avgHex ?>
                                    </small>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Progress bar -->
                <?php $done = (int)($sample['tests_completed'] ?? 0); ?>
                <div class="mt-2 mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Progress</span>
                        <span id="progressLabel"><?= $done ?>/12 tests</span>
                    </div>
                    <div class="progress" style="height:8px;">
                        <div class="progress-bar bg-success" id="progressBar"
                             style="width:<?= round($done / 12 * 100) ?>%"></div>
                    </div>
                </div>

                <div id="computeSection" class="<?= $allAveraged ? '' : 'd-none' ?>">
                    <div class="alert alert-success py-2">
                        <i class="fas fa-check-circle me-1"></i>
                        All 4 parameters have 3 readings each.
                        Ready to compute results.
                    </div>
                    <a href="results.php?sample_id=<?= $sample_id ?>"
                       class="btn btn-success w-100">
                        <i class="fas fa-calculator me-1"></i>
                        Compute &amp; View Results
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     ANALYSIS RESULTS (after full computation)
     ============================================================ -->
<?php if ($fullyAnalyzed): ?>
<div class="row mb-4">
    <div class="col-12">
        <h4><i class="fas fa-chart-bar me-2"></i>Soil Analysis Results</h4>
    </div>

    <?php
    $resultParams = [
        'ph'         => ['label'=>'Soil pH',       'value'=>$sample['ph_level'],         'unit'=>'',    'low'=>5.5,  'high'=>7.0,  'hex'=>$sample['ph_color_hex']],
        'nitrogen'   => ['label'=>'Nitrogen (N)',   'value'=>$sample['nitrogen_level'],   'unit'=>'ppm', 'low'=>20.0, 'high'=>40.0, 'hex'=>$sample['nitrogen_color_hex']],
        'phosphorus' => ['label'=>'Phosphorus (P)', 'value'=>$sample['phosphorus_level'], 'unit'=>'ppm', 'low'=>15.0, 'high'=>30.0, 'hex'=>$sample['phosphorus_color_hex']],
        'potassium'  => ['label'=>'Potassium (K)',  'value'=>$sample['potassium_level'],  'unit'=>'ppm', 'low'=>20.0, 'high'=>40.0, 'hex'=>$sample['potassium_color_hex']],
    ];
    foreach ($resultParams as $key => $rp):
        $status = getNutrientStatus($key, (float)$rp['value']);
        $bsColor = match($status) {
            'Optimal', 'High', 'Medium' => ($status === 'High' ? 'warning' : 'success'),
            default => 'danger'
        };
        if ($status === 'Acidic' || $status === 'Low') $bsColor = 'danger';
        if ($status === 'Medium') $bsColor = 'warning';
        if ($status === 'Optimal') $bsColor = 'success';
        if ($status === 'Alkaline') $bsColor = 'info';
        if ($status === 'High') $bsColor = 'primary';
    ?>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-<?= $bsColor ?>">
            <div class="card-header bg-<?= $bsColor ?> text-white text-center py-2">
                <strong><?= $rp['label'] ?></strong>
            </div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-<?= $bsColor ?>">
                    <?= number_format($rp['value'], 1) ?>
                    <small class="fs-6"><?= $rp['unit'] ?></small>
                </div>
                <div class="my-2">
                    <div style="width:50px;height:25px;background:<?= htmlspecialchars($rp['hex']) ?>;
                                border:1px solid #ccc;border-radius:4px;margin:0 auto;"></div>
                    <small class="text-muted"><?= htmlspecialchars($rp['hex']) ?></small>
                </div>
                <span class="badge bg-<?= $bsColor ?>"><?= $status ?></span>

                <!-- 3-test individual readings mini-table -->
                <?php if (!empty($readings[$key])): ?>
                <div class="mt-2 pt-2 border-top">
                    <small class="text-muted d-block mb-1">3-Test Readings</small>
                    <div class="d-flex justify-content-center gap-2">
                        <?php for ($t = 1; $t <= 3; $t++):
                            $rd = $readings[$key][$t] ?? null;
                        ?>
                        <div class="text-center">
                            <div style="width:28px;height:14px;background:<?= $rd ? $rd['color_hex'] : '#eee' ?>;
                                        border:1px solid #ccc;border-radius:2px;"></div>
                            <small style="font-size:9px;" class="text-muted">
                                #<?= $t ?><?= $rd ? ': ' . number_format($rd['computed_value'], 1) : '' ?>
                            </small>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── FERTILIZER RECOMMENDATION ─────────────────────────── -->
<?php if (!empty($fertRec)): ?>
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0">
            <i class="fas fa-spray-can me-2"></i>
            Fertilizer Recommendation
            <small class="fw-normal ms-2 text-muted" style="font-size:0.75rem;">
                Based on BSWM/PhilRice guidelines (per hectare)
            </small>
        </h5>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <!-- Lime -->
            <div class="col-md-3">
                <div class="card text-center h-100 <?= $fertRec['lime_tons'] > 0 ? 'border-danger' : 'border-secondary' ?>">
                    <div class="card-body py-3">
                        <i class="fas fa-mountain fa-2x <?= $fertRec['lime_tons'] > 0 ? 'text-danger' : 'text-muted' ?> mb-2"></i>
                        <div class="fw-bold fs-4 <?= $fertRec['lime_tons'] > 0 ? 'text-danger' : 'text-muted' ?>">
                            <?= number_format($fertRec['lime_tons'], 1) ?> t/ha
                        </div>
                        <small class="text-muted">Dolomitic Lime</small>
                        <div style="font-size:10px;" class="text-muted mt-1">for pH correction</div>
                    </div>
                </div>
            </div>
            <!-- Urea -->
            <div class="col-md-3">
                <div class="card text-center h-100 border-success">
                    <div class="card-body py-3">
                        <i class="fas fa-seedling fa-2x text-success mb-2"></i>
                        <div class="fw-bold fs-4 text-success">
                            <?= number_format($fertRec['urea_bags'], 1) ?> bags/ha
                        </div>
                        <small class="text-muted">Urea (46-0-0)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Nitrogen source</div>
                    </div>
                </div>
            </div>
            <!-- TSP -->
            <div class="col-md-3">
                <div class="card text-center h-100 border-primary">
                    <div class="card-body py-3">
                        <i class="fas fa-atom fa-2x text-primary mb-2"></i>
                        <div class="fw-bold fs-4 text-primary">
                            <?= number_format($fertRec['tsp_bags'], 1) ?> bags/ha
                        </div>
                        <small class="text-muted">TSP (0-46-0)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Phosphorus source</div>
                    </div>
                </div>
            </div>
            <!-- MOP -->
            <div class="col-md-3">
                <div class="card text-center h-100 border-info">
                    <div class="card-body py-3">
                        <i class="fas fa-flask fa-2x text-info mb-2"></i>
                        <div class="fw-bold fs-4 text-info">
                            <?= number_format($fertRec['mop_bags'], 1) ?> bags/ha
                        </div>
                        <small class="text-muted">MOP (0-0-60)</small>
                        <div style="font-size:10px;" class="text-muted mt-1">Potassium source</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advisory notes -->
        <ul class="list-group list-group-flush">
            <?php foreach ($fertRec['notes'] as $note): ?>
            <li class="list-group-item py-1">
                <i class="fas fa-circle-info text-warning me-2"></i>
                <small><?= htmlspecialchars($note) ?></small>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- ── CROP RECOMMENDATIONS ──────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-seedling me-2"></i>Crop Recommendations
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($recommendations)): ?>
            <div class="alert alert-warning mb-0">
                No crop match for these soil conditions.
                Consider soil amendments to adjust pH or NPK levels.
            </div>
        <?php else: ?>
            <p class="text-muted small mb-3">
                Scored by how many parameters fall within the crop's tolerance range (4 = perfect match).
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle table-sm">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <th>Crop</th>
                            <th>pH Range</th>
                            <th>N (ppm)</th>
                            <th>P (ppm)</th>
                            <th>K (ppm)</th>
                            <th>Match</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recommendations as $i => $crop): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($crop['name']) ?></strong>
                                <?php if ($i === 0): ?>
                                    <span class="badge bg-warning text-dark ms-1">Top Pick</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $crop['min_ph'] ?> – <?= $crop['max_ph'] ?></td>
                            <td><?= $crop['min_nitrogen'] ?> – <?= $crop['max_nitrogen'] ?></td>
                            <td><?= $crop['min_phosphorus'] ?> – <?= $crop['max_phosphorus'] ?></td>
                            <td><?= $crop['min_potassium'] ?> – <?= $crop['max_potassium'] ?></td>
                            <td>
                                <?php
                                $s = $crop['match_score'];
                                $mc = $s == 4 ? 'success' : ($s >= 3 ? 'warning' : ($s >= 2 ? 'info' : 'danger'));
                                ?>
                                <span class="badge bg-<?= $mc ?>"><?= $s ?>/4</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── AI RECOMMENDATION ─────────────────────────────────── -->
<div class="card mb-4" id="aiSection">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">
            <i class="fas fa-robot me-2"></i>AI Agronomic Advisor
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($sample['ai_recommendation'])): ?>
            <div id="aiRecommendationText" class="p-3 bg-light rounded" style="white-space:pre-wrap;">
                <?= htmlspecialchars($sample['ai_recommendation']) ?>
            </div>
            <div class="mt-2 text-end">
                <button class="btn btn-sm btn-outline-dark" onclick="generateAI()">
                    <i class="fas fa-sync me-1"></i> Regenerate
                </button>
            </div>
        <?php else: ?>
            <p class="text-muted">
                Get AI-powered agronomic advice tailored to your exact soil readings,
                crop recommendations, and local conditions.
            </p>
            <button class="btn btn-dark" onclick="generateAI()" id="aiBtn">
                <i class="fas fa-robot me-1"></i> Generate AI Recommendation
            </button>
            <div id="aiLoading" class="d-none mt-3">
                <div class="spinner-border spinner-border-sm text-dark me-2"></div>
                Consulting AI agronomist...
            </div>
            <div id="aiResult" class="mt-3 p-3 bg-light rounded d-none"
                 style="white-space:pre-wrap;"></div>
            <div id="aiError" class="alert alert-danger mt-3 d-none"></div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Re-analyze button ──────────────────────────────────── -->
<div class="row mb-4">
    <div class="col text-end">
        <a href="results.php?sample_id=<?= $sample_id ?>&redo=1"
           class="btn btn-outline-warning"
           onclick="return confirm('This will reset ALL readings for this sample. Continue?');">
            <i class="fas fa-redo me-1"></i> Re-capture All
        </a>
        <a href="export_excel.php?sample_id=<?= $sample_id ?>"
           class="btn btn-success ms-2">
            <i class="fas fa-file-excel me-1"></i> Export to Excel
        </a>
        <a href="analysis.php" class="btn btn-primary ms-2">
            <i class="fas fa-plus-circle me-1"></i> New Sample
        </a>
    </div>
</div>

<?php endif; // fullyAnalyzed ?>

<!-- ============================================================
     WEBCAM JAVASCRIPT — 3-test capture system
     ============================================================ -->
<?php if (!$fullyAnalyzed): ?>
<script>
const sampleId = <?= $sample_id ?>;
let videoStream = null;
let totalReadings = <?= (int)($sample['tests_completed'] ?? 0) ?>;

// Track how many tests are done per parameter (pre-populate from PHP)
const testsDone = {
    ph:         <?= count($readings['ph']) ?>,
    nitrogen:   <?= count($readings['nitrogen']) ?>,
    phosphorus: <?= count($readings['phosphorus']) ?>,
    potassium:  <?= count($readings['potassium']) ?>,
};

function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { width: 380, height: 280 }, audio: false })
        .then(stream => {
            videoStream = stream;
            document.getElementById('webcam').srcObject = stream;
            const btn = document.getElementById('startCameraBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Camera Active';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
        })
        .catch(err => alert('Could not access webcam: ' + err.message));
}

function captureTest(parameter, testNumber) {
    if (!videoStream) {
        alert('Please start the camera first.');
        return;
    }

    const video  = document.getElementById('webcam');
    const canvas = document.getElementById('snapshot');
    const ctx    = canvas.getContext('2d');

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Sample central 80×80 pixel region (averaged)
    const cx   = Math.floor(canvas.width  / 2) - 40;
    const cy   = Math.floor(canvas.height / 2) - 40;
    const data = ctx.getImageData(cx, cy, 80, 80).data;

    let r = 0, g = 0, b = 0, n = 0;
    for (let i = 0; i < data.length; i += 4) {
        r += data[i]; g += data[i + 1]; b += data[i + 2]; n++;
    }
    r = Math.round(r / n); g = Math.round(g / n); b = Math.round(b / n);
    const hex = '#' + [r, g, b].map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();

    // Disable the capture button while saving
    const btn = document.getElementById(`btn-${parameter}-${testNumber}`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }

    fetch('save_soil_parameters.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sample_id: sampleId, parameter, color_hex: hex,
                               r, g, b, test_number: testNumber })
    })
    .then(res => res.json())
    .then(resp => {
        if (!resp.success) { alert('Error: ' + resp.message); return; }

        testsDone[parameter] = resp.tests_done;
        totalReadings = resp.total_readings;

        // Update progress bar
        document.getElementById('progressLabel').textContent = totalReadings + '/12 tests';
        document.getElementById('progressBar').style.width  = Math.round(totalReadings / 12 * 100) + '%';

        // Refresh the row by reloading the page for that parameter column
        // (simplest reliable approach for showing updated swatches)
        if (resp.avg_hex) {
            // All 3 tests done for this param — update average cell
            const avgCell = document.getElementById(`avg-cell-${parameter}`);
            if (avgCell) {
                avgCell.innerHTML = `
                    <div style="width:40px;height:20px;background:${resp.avg_hex};
                                border:1px solid #999;border-radius:3px;margin:auto;"></div>
                    <small class="text-success fw-bold" style="font-size:10px;">${resp.avg_hex}</small>`;
            }
        }

        // Check if all 12 tests are done
        const allDone = Object.values(testsDone).every(c => c >= 3);
        if (allDone || totalReadings >= 12) {
            document.getElementById('computeSection').classList.remove('d-none');
        }

        // Reload to refresh the capture table with updated swatches
        setTimeout(() => location.reload(), 600);
    })
    .catch(() => {
        alert('Network error — please try again.');
        if (btn) { btn.disabled = false; btn.innerHTML = `<i class="fas fa-camera"></i> #${testNumber}`; }
    });
}
</script>
<?php endif; ?>

<!-- AI Recommendation JavaScript -->
<?php if ($fullyAnalyzed): ?>
<script>
function generateAI() {
    const btn      = document.getElementById('aiBtn');
    const loading  = document.getElementById('aiLoading');
    const result   = document.getElementById('aiResult');
    const errDiv   = document.getElementById('aiError');

    if (btn)     btn.disabled = true;
    if (loading) loading.classList.remove('d-none');
    if (result)  result.classList.add('d-none');
    if (errDiv)  errDiv.classList.add('d-none');

    fetch('ai_recommendation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sample_id: <?= $sample_id ?> })
    })
    .then(res => res.json())
    .then(data => {
        if (loading) loading.classList.add('d-none');
        if (data.success) {
            if (result) {
                result.textContent = data.recommendation;
                result.classList.remove('d-none');
            }
            // Also update in-page text if it existed already
            const existing = document.getElementById('aiRecommendationText');
            if (existing) existing.textContent = data.recommendation;
        } else {
            if (errDiv) {
                errDiv.textContent = 'AI Error: ' + data.message;
                errDiv.classList.remove('d-none');
            }
            if (btn) btn.disabled = false;
        }
    })
    .catch(() => {
        if (loading) loading.classList.add('d-none');
        if (errDiv) {
            errDiv.textContent = 'Network error contacting AI service.';
            errDiv.classList.remove('d-none');
        }
        if (btn) btn.disabled = false;
    });
}
</script>
<?php endif; ?>

<?php else: ?>
<!-- ============================================================
     LIST VIEW
     ============================================================ -->
<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-list-alt me-2"></i>Soil Sample Results</h2>
        <p class="lead text-muted">
            <?= isAdmin() ? 'All system samples' : 'Your submitted soil samples' ?>
        </p>
    </div>
    <div class="col-md-4 text-end">
        <a href="analysis.php" class="btn btn-success me-2">
            <i class="fas fa-plus-circle me-1"></i> New Sample
        </a>
        <?php if (isAdmin()): ?>
        <a href="export_excel.php" class="btn btn-outline-success">
            <i class="fas fa-file-excel me-1"></i> Export All
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($samples)): ?>
            <div class="text-center py-5">
                <i class="fas fa-vial fa-3x text-muted mb-3"></i>
                <p class="text-muted">No soil samples yet.</p>
                <a href="analysis.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Add Your First Sample
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <?php if (isAdmin()): ?><th>User</th><?php endif; ?>
                            <th>Sample Name</th>
                            <th>Farmer</th>
                            <th>Date Received</th>
                            <th>pH</th>
                            <th>N</th>
                            <th>P</th>
                            <th>K</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($samples as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <?php if (isAdmin()): ?>
                            <td><?= htmlspecialchars($s['username']) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($s['sample_name']) ?></td>
                            <td><?= htmlspecialchars($s['farmer_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($s['sample_date'])) ?></td>
                            <td><?= !is_null($s['ph_level'])         ? number_format($s['ph_level'],         1) : '—' ?></td>
                            <td><?= !is_null($s['nitrogen_level'])   ? number_format($s['nitrogen_level'],   1) : '—' ?></td>
                            <td><?= !is_null($s['phosphorus_level']) ? number_format($s['phosphorus_level'], 1) : '—' ?></td>
                            <td><?= !is_null($s['potassium_level'])  ? number_format($s['potassium_level'],  1) : '—' ?></td>
                            <td>
                                <?php if (!is_null($s['fertility_score'])): ?>
                                    <?php
                                    $fc = $s['fertility_score'] >= 75 ? 'success'
                                        : ($s['fertility_score'] >= 50 ? 'warning' : 'danger');
                                    ?>
                                    <span class="badge bg-<?= $fc ?>">
                                        <?= $s['fertility_score'] ?>%
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td>
                                <?php if (!is_null($s['analyzed_at'])): ?>
                                    <span class="badge bg-success">Analyzed</span>
                                <?php elseif ($s['ph_color_hex']): ?>
                                    <span class="badge bg-warning text-dark">In Progress</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="results.php?sample_id=<?= $s['id'] ?>"
                                   class="btn btn-sm btn-outline-primary me-1">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if (!is_null($s['analyzed_at'])): ?>
                                <a href="export_excel.php?sample_id=<?= $s['id'] ?>"
                                   class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-file-excel"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; // list vs detail ?>

<?php include 'includes/footer.php'; ?>
