<?php
// ============================================================
// ai_recommendation.php — Claude AI agronomic advisor endpoint
// Accepts: POST JSON { sample_id: int }
// Returns: JSON { success: bool, recommendation: string }
// ============================================================
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
$sample_id = intval($input['sample_id'] ?? 0);

if ($sample_id < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid sample_id']);
    exit;
}

// ── Fetch sample ─────────────────────────────────────────────
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
    echo json_encode(['success' => false, 'message' => 'Sample not found or access denied']);
    exit;
}

if (is_null($sample['ph_level'])) {
    echo json_encode(['success' => false, 'message' => 'Sample has not been analyzed yet']);
    exit;
}

// ── Check API key ─────────────────────────────────────────────
if (empty(ANTHROPIC_API_KEY)) {
    echo json_encode([
        'success' => false,
        'message' => 'AI service is not configured. Set the ANTHROPIC_API_KEY environment variable.',
    ]);
    exit;
}

// ── Build fertilizer recommendation summary ───────────────────
$fertRec = getFertilizerRecommendation(
    (float)$sample['ph_level'],
    (float)$sample['nitrogen_level'],
    (float)$sample['phosphorus_level'],
    (float)$sample['potassium_level']
);
$fertSummary = sprintf(
    "Lime: %.1f t/ha | Urea (46-0-0): %.1f bags/ha | TSP (0-46-0): %.1f bags/ha | MOP (0-0-60): %.1f bags/ha",
    $fertRec['lime_tons'],
    $fertRec['urea_bags'],
    $fertRec['tsp_bags'],
    $fertRec['mop_bags']
);

// ── Fetch top 3 crop recommendations ─────────────────────────
$recStmt = $pdo->prepare(
    "SELECT name,
       (CASE WHEN :ph BETWEEN min_ph AND max_ph THEN 1 ELSE 0 END +
        CASE WHEN :n  BETWEEN min_nitrogen AND max_nitrogen THEN 1 ELSE 0 END +
        CASE WHEN :p  BETWEEN min_phosphorus AND max_phosphorus THEN 1 ELSE 0 END +
        CASE WHEN :k  BETWEEN min_potassium AND max_potassium THEN 1 ELSE 0 END)
       AS score
     FROM crops
     ORDER BY score DESC, name ASC
     LIMIT 3"
);
$recStmt->execute([
    ':ph' => $sample['ph_level'], ':n' => $sample['nitrogen_level'],
    ':p'  => $sample['phosphorus_level'], ':k' => $sample['potassium_level'],
]);
$topCrops = array_column($recStmt->fetchAll(), 'name');
$topCropsStr = implode(', ', $topCrops) ?: 'None matched';

// ── Build AI prompt ───────────────────────────────────────────
$phStatus = getNutrientStatus('ph',         (float)$sample['ph_level']);
$nStatus  = getNutrientStatus('nitrogen',   (float)$sample['nitrogen_level']);
$pStatus  = getNutrientStatus('phosphorus', (float)$sample['phosphorus_level']);
$kStatus  = getNutrientStatus('potassium',  (float)$sample['potassium_level']);

$prompt = <<<PROMPT
You are an expert agronomist advising Filipino farmers through the Office of the Municipal Agriculturist (OMA).
Provide practical, actionable advice based on the soil test results below.
Write in clear, plain English suitable for farmers. Keep the total response under 400 words.
Structure your advice in 3 sections:
1. SOIL HEALTH ASSESSMENT — brief interpretation of pH and NPK status
2. FERTILIZER APPLICATION PLAN — confirm/expand the automated recommendation with practical timing and application tips
3. CROP & PLANTING ADVICE — specific tips for the top recommended crops in Philippine conditions

SOIL TEST RESULTS:
- Farmer: {$sample['farmer_name']}
- Location: {$sample['address']}{$sample['location']}
- pH Level: {$sample['ph_level']} ({$phStatus})
- Nitrogen (N): {$sample['nitrogen_level']} ppm ({$nStatus})
- Phosphorus (P): {$sample['phosphorus_level']} ppm ({$pStatus})
- Potassium (K): {$sample['potassium_level']} ppm ({$kStatus})
- Fertility Score: {$sample['fertility_score']}%
- Recommended Crops (top matches): {$topCropsStr}

AUTOMATED FERTILIZER RECOMMENDATION (per hectare):
{$fertSummary}

Respond only with the three-section advice. Do not repeat the input data.
PROMPT;

// ── Call Anthropic Claude API ─────────────────────────────────
$payload = json_encode([
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 1024,
    'messages'   => [
        ['role' => 'user', 'content' => $prompt]
    ],
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: '         . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS     => $payload,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    error_log("AI recommendation curl error: $curlError");
    echo json_encode(['success' => false, 'message' => 'Failed to connect to AI service']);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200 || empty($data['content'][0]['text'])) {
    $errMsg = $data['error']['message'] ?? "HTTP $httpCode";
    error_log("AI recommendation API error: $errMsg — Response: $response");
    echo json_encode(['success' => false, 'message' => "AI API error: $errMsg"]);
    exit;
}

$recommendation = trim($data['content'][0]['text']);

// ── Persist to database ───────────────────────────────────────
$pdo->prepare(
    "UPDATE soil_samples SET ai_recommendation = ? WHERE id = ?"
)->execute([$recommendation, $sample_id]);

echo json_encode([
    'success'        => true,
    'recommendation' => $recommendation,
]);
