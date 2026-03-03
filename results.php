<?php
// ============================================================
// results.php — Soil sample results + webcam analysis + crop
//               recommendations
// ============================================================
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// ── Fetch one sample (detail view) or all samples (list view) ─

$sample    = null;
$sample_id = isset($_GET['sample_id']) ? intval($_GET['sample_id']) : 0;

if ($sample_id > 0) {
    // Detail view – verify ownership (admin can view any)
    if (isAdmin()) {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              WHERE s.id = ?"
        );
        $stmt->execute([$sample_id]);
    } else {
        $stmt = $pdo->prepare(
            "SELECT s.*, u.username
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              WHERE s.id = ? AND s.user_id = ?"
        );
        $stmt->execute([$sample_id, $_SESSION['user_id']]);
    }
    $sample = $stmt->fetch();

    if (!$sample) {
        $_SESSION['error_message'] = 'Sample not found or access denied.';
        redirect('results.php');
    }

    // ── Auto-compute numeric values when all 4 colours are captured ──
    $allCaptured = $sample['ph_color_hex']
                && $sample['nitrogen_color_hex']
                && $sample['phosphorus_color_hex']
                && $sample['potassium_color_hex'];

    if ($allCaptured && is_null($sample['ph_level'])) {
        $ph = colorToPhLevel($sample['ph_color_hex']);
        $n  = colorToNitrogenLevel($sample['nitrogen_color_hex']);
        $p  = colorToPhosphorusLevel($sample['phosphorus_color_hex']);
        $k  = colorToPotassiumLevel($sample['potassium_color_hex']);
        $fs = computeFertilityScore($ph, $n, $p, $k);

        $upd = $pdo->prepare(
            "UPDATE soil_samples
                SET ph_level = ?, nitrogen_level = ?, phosphorus_level = ?,
                    potassium_level = ?, fertility_score = ?, analyzed_at = NOW()
              WHERE id = ?"
        );
        $upd->execute([$ph, $n, $p, $k, $fs, $sample_id]);

        // Reload so the page shows the fresh values
        redirect("results.php?sample_id=$sample_id");
    }

    // ── Crop recommendations (only after analysis is complete) ───────
    $recommendations = [];
    if (!is_null($sample['ph_level'])) {
        // Hard match: all four parameters must be within crop tolerance
        $recStmt = $pdo->prepare(
            "SELECT *, (
                  (CASE WHEN :ph  BETWEEN min_ph          AND max_ph          THEN 1 ELSE 0 END) +
                  (CASE WHEN :n   BETWEEN min_nitrogen     AND max_nitrogen     THEN 1 ELSE 0 END) +
                  (CASE WHEN :p   BETWEEN min_phosphorus   AND max_phosphorus   THEN 1 ELSE 0 END) +
                  (CASE WHEN :k   BETWEEN min_potassium    AND max_potassium    THEN 1 ELSE 0 END)
             ) AS match_score
               FROM crops
              WHERE min_ph <= :ph2 AND max_ph >= :ph3
              ORDER BY match_score DESC, name ASC"
        );
        $recStmt->execute([
            ':ph'  => $sample['ph_level'],
            ':n'   => $sample['nitrogen_level'],
            ':p'   => $sample['phosphorus_level'],
            ':k'   => $sample['potassium_level'],
            ':ph2' => $sample['ph_level'],
            ':ph3' => $sample['ph_level'],
        ]);
        $recommendations = $recStmt->fetchAll();
    }

} else {
    // List view – user sees only their own samples; admin sees all
    if (isAdmin()) {
        $samples = $pdo->query(
            "SELECT s.*, u.username
               FROM soil_samples s
               JOIN users u ON s.user_id = u.id
              ORDER BY s.created_at DESC"
        )->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            "SELECT * FROM soil_samples
              WHERE user_id = ?
              ORDER BY created_at DESC"
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
    </div>
</div>

<!-- Sample info card -->
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-vial me-2"></i>
                    <?php echo htmlspecialchars($sample['sample_name']); ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Farmer:</strong> <?php echo htmlspecialchars($sample['farmer_name']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($sample['address']); ?></p>
                        <p><strong>Farm Location:</strong> <?php echo htmlspecialchars($sample['location'] ?? '—'); ?></p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Date Received:</strong> <?php echo date('F j, Y', strtotime($sample['sample_date'])); ?></p>
                        <p><strong>Date Tested:</strong>   <?php echo date('F j, Y', strtotime($sample['date_tested'])); ?></p>
                        <?php if ($sample['analyzed_at']): ?>
                            <p><strong>Analyzed:</strong> <?php echo date('F j, Y g:i A', strtotime($sample['analyzed_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-center">
                        <?php if (!is_null($sample['fertility_score'])): ?>
                            <div class="display-4 fw-bold text-<?php
                                echo $sample['fertility_score'] >= 80 ? 'success'
                                   : ($sample['fertility_score'] >= 60 ? 'warning' : 'danger');
                            ?>">
                                <?php echo $sample['fertility_score']; ?>%
                            </div>
                            <p class="text-muted">Fertility Score</p>
                        <?php else: ?>
                            <p class="text-muted"><i>Not yet analyzed</i></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── WEBCAM CAPTURE SECTION (shown only when analysis is incomplete) ── -->
<?php
$captured = [
    'ph'         => !empty($sample['ph_color_hex']),
    'nitrogen'   => !empty($sample['nitrogen_color_hex']),
    'phosphorus' => !empty($sample['phosphorus_color_hex']),
    'potassium'  => !empty($sample['potassium_color_hex']),
];
$fullyAnalyzed = !is_null($sample['ph_level']);
?>
<?php if (!$fullyAnalyzed): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Webcam Color Capture</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Place each soil indicator strip under the webcam, centre it in the target circle,
                    then click the matching <strong>Capture</strong> button.
                </p>

                <!-- Webcam feed -->
                <div class="row">
                    <div class="col-md-6 text-center">
                        <div style="position:relative; display:inline-block;">
                            <video id="webcam" width="400" height="300"
                                   autoplay playsinline
                                   style="border:2px solid #ccc; border-radius:8px;"></video>
                            <!-- Crosshair target -->
                            <div style="
                                position:absolute; top:50%; left:50%;
                                transform:translate(-50%,-50%);
                                width:80px; height:80px;
                                border:3px dashed rgba(255,255,255,0.8);
                                border-radius:50%; pointer-events:none;">
                            </div>
                        </div>
                        <canvas id="snapshot" width="400" height="300"
                                style="display:none;"></canvas>
                        <br>
                        <button id="startCameraBtn" class="btn btn-outline-secondary mt-2"
                                onclick="startCamera()">
                            <i class="fas fa-video"></i> Start Camera
                        </button>
                    </div>

                    <!-- Capture buttons per parameter -->
                    <div class="col-md-6">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Captured Color</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $params = [
                                    'ph'         => ['label' => 'Soil pH',         'icon' => 'fa-flask'],
                                    'nitrogen'   => ['label' => 'Nitrogen (N)',     'icon' => 'fa-leaf'],
                                    'phosphorus' => ['label' => 'Phosphorus (P)',   'icon' => 'fa-atom'],
                                    'potassium'  => ['label' => 'Potassium (K)',    'icon' => 'fa-seedling'],
                                ];
                                foreach ($params as $key => $meta):
                                    $col    = $key . '_color_hex';
                                    $hexVal = $sample[$col] ?? null;
                                ?>
                                <tr id="row-<?php echo $key; ?>">
                                    <td>
                                        <i class="fas <?php echo $meta['icon']; ?> me-1"></i>
                                        <?php echo $meta['label']; ?>
                                    </td>
                                    <td>
                                        <div id="swatch-<?php echo $key; ?>"
                                             style="width:60px; height:30px; border-radius:4px;
                                                    background:<?php echo $hexVal ?? '#eee'; ?>;
                                                    border:1px solid #ccc; display:inline-block;">
                                        </div>
                                        <small id="hex-<?php echo $key; ?>" class="ms-1 text-muted">
                                            <?php echo $hexVal ?? '—'; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($captured[$key]): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Done
                                            </span>
                                            <button class="btn btn-sm btn-outline-secondary ms-1"
                                                    onclick="captureColor('<?php echo $key; ?>')">
                                                Redo
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success"
                                                    onclick="captureColor('<?php echo $key; ?>')">
                                                <i class="fas fa-camera"></i> Capture
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div id="computeSection" style="display:<?php echo (array_sum($captured) === 4) ? 'block' : 'none'; ?>">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                All 4 parameters captured! Click below to compute results.
                            </div>
                            <a href="results.php?sample_id=<?php echo $sample_id; ?>"
                               class="btn btn-success btn-lg w-100">
                                <i class="fas fa-calculator"></i> Compute &amp; View Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── ANALYSIS RESULTS (shown once all colors are captured & computed) ── -->
<?php if ($fullyAnalyzed): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <h4><i class="fas fa-chart-bar me-2"></i>Soil Analysis Results</h4>
    </div>

    <?php
    $resultParams = [
        'ph'         => ['label' => 'Soil pH',         'value' => $sample['ph_level'],         'unit' => '',    'optimal' => '6.0 – 7.0', 'color_hex' => $sample['ph_color_hex']],
        'nitrogen'   => ['label' => 'Nitrogen (N)',     'value' => $sample['nitrogen_level'],   'unit' => 'ppm', 'optimal' => '20 – 60 ppm', 'color_hex' => $sample['nitrogen_color_hex']],
        'phosphorus' => ['label' => 'Phosphorus (P)',   'value' => $sample['phosphorus_level'], 'unit' => 'ppm', 'optimal' => '15 – 40 ppm', 'color_hex' => $sample['phosphorus_color_hex']],
        'potassium'  => ['label' => 'Potassium (K)',    'value' => $sample['potassium_level'],  'unit' => 'ppm', 'optimal' => '20 – 50 ppm', 'color_hex' => $sample['potassium_color_hex']],
    ];
    foreach ($resultParams as $key => $rp):
        // Determine status badge
        if ($key === 'ph') {
            $status = ($rp['value'] >= 6.0 && $rp['value'] <= 7.0) ? 'success' : (($rp['value'] >= 5.5 && $rp['value'] <= 7.5) ? 'warning' : 'danger');
        } else {
            $ranges = ['nitrogen' => [20,60], 'phosphorus' => [15,40], 'potassium' => [20,50]];
            [$lo, $hi] = $ranges[$key];
            $status = ($rp['value'] >= $lo && $rp['value'] <= $hi) ? 'success' : (($rp['value'] >= $lo*0.5) ? 'warning' : 'danger');
        }
        $statusLabel = ['success' => 'Optimal', 'warning' => 'Moderate', 'danger' => 'Low/High'][$status];
    ?>
    <div class="col-md-3 mb-3">
        <div class="card h-100 border-<?php echo $status; ?>">
            <div class="card-header bg-<?php echo $status; ?> text-white text-center py-2">
                <strong><?php echo $rp['label']; ?></strong>
            </div>
            <div class="card-body text-center">
                <div class="display-6 fw-bold text-<?php echo $status; ?>">
                    <?php echo number_format($rp['value'], 1); ?>
                    <small class="fs-6"><?php echo $rp['unit']; ?></small>
                </div>
                <div class="mt-2 mb-2">
                    <div style="width:50px; height:25px; background:<?php echo htmlspecialchars($rp['color_hex']); ?>;
                                border:1px solid #ccc; border-radius:4px; margin:0 auto;"></div>
                    <small class="text-muted"><?php echo htmlspecialchars($rp['color_hex']); ?></small>
                </div>
                <span class="badge bg-<?php echo $status; ?>"><?php echo $statusLabel; ?></span>
                <p class="text-muted small mt-2 mb-0">Optimal: <?php echo $rp['optimal']; ?></p>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── CROP RECOMMENDATIONS ─────────────────────────────────────────── -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-seedling me-2"></i>Crop Recommendations
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recommendations)): ?>
                    <div class="alert alert-warning mb-0">
                        No crop match found for these soil conditions.
                        Consider soil amendments to adjust pH or NPK levels.
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-3">
                        Crops are ranked by how many parameters fall within their tolerance range
                        (4 = perfect match).
                    </p>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-success">
                                <tr>
                                    <th>#</th>
                                    <th>Crop</th>
                                    <th>pH Range</th>
                                    <th>N (ppm)</th>
                                    <th>P (ppm)</th>
                                    <th>K (ppm)</th>
                                    <th>Match</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recommendations as $i => $crop): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($crop['name']); ?></strong></td>
                                    <td><?php echo $crop['min_ph']; ?> – <?php echo $crop['max_ph']; ?></td>
                                    <td><?php echo $crop['min_nitrogen']; ?> – <?php echo $crop['max_nitrogen']; ?></td>
                                    <td><?php echo $crop['min_phosphorus']; ?> – <?php echo $crop['max_phosphorus']; ?></td>
                                    <td><?php echo $crop['min_potassium']; ?> – <?php echo $crop['max_potassium']; ?></td>
                                    <td>
                                        <?php
                                        $score = $crop['match_score'];
                                        $matchColor = $score == 4 ? 'success' : ($score >= 2 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?php echo $matchColor; ?>">
                                            <?php echo $score; ?>/4
                                        </span>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo htmlspecialchars(mb_strimwidth($crop['description'] ?? '', 0, 60, '…')); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Re-analyze button -->
<div class="row mb-4">
    <div class="col text-end">
        <a href="results.php?sample_id=<?php echo $sample_id; ?>&redo=1"
           class="btn btn-outline-warning"
           onclick="return confirm('Re-capture will reset all color readings. Continue?');">
            <i class="fas fa-redo"></i> Re-capture Colors
        </a>
        <a href="analysis.php" class="btn btn-success ms-2">
            <i class="fas fa-plus-circle"></i> New Sample
        </a>
    </div>
</div>
<?php endif; // fullyAnalyzed ?>

<!-- ── WEBCAM JS ───────────────────────────────────────────────────── -->
<?php if (!$fullyAnalyzed): ?>
<script>
const sampleId   = <?php echo $sample_id; ?>;
let videoStream  = null;
let capturedCount = <?php echo array_sum($captured); ?>;

function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { width: 400, height: 300 }, audio: false })
        .then(stream => {
            videoStream = stream;
            const video = document.getElementById('webcam');
            video.srcObject = stream;
            document.getElementById('startCameraBtn').disabled = true;
            document.getElementById('startCameraBtn').textContent = 'Camera active';
        })
        .catch(err => {
            alert('Could not access webcam: ' + err.message);
        });
}

function captureColor(parameter) {
    const video    = document.getElementById('webcam');
    const canvas   = document.getElementById('snapshot');
    const ctx      = canvas.getContext('2d');

    if (!videoStream) {
        alert('Please start the camera first.');
        return;
    }

    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    // Sample the central 80×80 pixel region
    const cx   = Math.floor(canvas.width  / 2) - 40;
    const cy   = Math.floor(canvas.height / 2) - 40;
    const data = ctx.getImageData(cx, cy, 80, 80).data;

    let r = 0, g = 0, b = 0, n = 0;
    for (let i = 0; i < data.length; i += 4) {
        r += data[i]; g += data[i + 1]; b += data[i + 2]; n++;
    }
    r = Math.round(r / n);
    g = Math.round(g / n);
    b = Math.round(b / n);

    const hex = '#' + [r, g, b]
        .map(v => v.toString(16).padStart(2, '0'))
        .join('');

    fetch('save_soil_parameters.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sample_id: sampleId, parameter, color_hex: hex, r, g, b })
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.success) {
            // Update swatch + hex label live
            document.getElementById('swatch-' + parameter).style.background = hex;
            document.getElementById('hex-'    + parameter).textContent       = hex;

            capturedCount++;
            if (capturedCount >= 4) {
                document.getElementById('computeSection').style.display = 'block';
            }
        } else {
            alert('Error saving color: ' + resp.message);
        }
    })
    .catch(() => alert('Network error — please try again.'));
}
</script>
<?php endif; ?>

<?php
// ── Handle "redo" — reset all color readings ───────────────────────
if (isset($_GET['redo']) && $_GET['redo'] == 1 && $sample) {
    $pdo->prepare(
        "UPDATE soil_samples
            SET ph_color_hex = NULL, nitrogen_color_hex = NULL,
                phosphorus_color_hex = NULL, potassium_color_hex = NULL,
                ph_level = NULL, nitrogen_level = NULL,
                phosphorus_level = NULL, potassium_level = NULL,
                fertility_score = NULL, analyzed_at = NULL
          WHERE id = ?"
    )->execute([$sample_id]);
    redirect("results.php?sample_id=$sample_id");
}
?>

<?php else: ?>
<!-- ============================================================
     LIST VIEW — all samples
     ============================================================ -->

<div class="row mb-3">
    <div class="col-md-8">
        <h2><i class="fas fa-list-alt me-2"></i>Soil Sample Results</h2>
        <p class="lead text-muted">
            <?php echo isAdmin() ? 'All system samples' : 'Your submitted soil samples'; ?>
        </p>
    </div>
    <div class="col-md-4 text-end">
        <a href="analysis.php" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> New Sample
        </a>
    </div>
</div>

<!-- Flash messages -->
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
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
                <table class="table table-striped table-hover align-middle" id="samplesTable">
                    <thead class="table-success">
                        <tr>
                            <th>#</th>
                            <?php if (isAdmin()): ?><th>User</th><?php endif; ?>
                            <th>Sample Name</th>
                            <th>Farmer</th>
                            <th>Date Received</th>
                            <th>pH</th>
                            <th>N (ppm)</th>
                            <th>P (ppm)</th>
                            <th>K (ppm)</th>
                            <th>Fertility</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($samples as $i => $s): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <?php if (isAdmin()): ?>
                                <td><?php echo htmlspecialchars($s['username']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($s['sample_name']); ?></td>
                            <td><?php echo htmlspecialchars($s['farmer_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($s['sample_date'])); ?></td>
                            <td><?php echo !is_null($s['ph_level'])         ? number_format($s['ph_level'],         1) : '—'; ?></td>
                            <td><?php echo !is_null($s['nitrogen_level'])   ? number_format($s['nitrogen_level'],   1) : '—'; ?></td>
                            <td><?php echo !is_null($s['phosphorus_level']) ? number_format($s['phosphorus_level'], 1) : '—'; ?></td>
                            <td><?php echo !is_null($s['potassium_level'])  ? number_format($s['potassium_level'],  1) : '—'; ?></td>
                            <td>
                                <?php if (!is_null($s['fertility_score'])): ?>
                                    <span class="badge bg-<?php
                                        echo $s['fertility_score'] >= 80 ? 'success'
                                           : ($s['fertility_score'] >= 60 ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo $s['fertility_score']; ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
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
                                <a href="results.php?sample_id=<?php echo $s['id']; ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; // list vs detail view ?>

<?php include 'includes/footer.php'; ?>
