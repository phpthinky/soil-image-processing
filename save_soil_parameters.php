<?php
require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['sample_id']) || !isset($input['parameter']) || !isset($input['color_hex']) || !isset($input['r']) || !isset($input['g']) || !isset($input['b'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$sample_id = intval($input['sample_id']);
$parameter = $input['parameter'];
$color_hex = $input['color_hex'];
$r = intval($input['r']);
$g = intval($input['g']);
$b = intval($input['b']);

// Validate parameter
$valid_parameters = ['ph', 'nitrogen', 'phosphorus', 'potassium'];
if (!in_array($parameter, $valid_parameters)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid parameter']);
    exit;
}

// Validate color values
if (!preg_match('/^#[0-9A-F]{6}$/i', $color_hex)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid color hex format']);
    exit;
}

if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid RGB values']);
    exit;
}

try {
    // Verify the sample exists and belongs to the user
    $stmt = $pdo->prepare("SELECT id, user_id FROM soil_samples WHERE id = ?");
    $stmt->execute([$sample_id]);
    $sample = $stmt->fetch();

    if (!$sample) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sample not found']);
        exit;
    }

    // Check if user owns the sample or is admin
    if ($sample['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this sample']);
        exit;
    }

    // Determine which column to update based on parameter
    $color_column = $parameter . '_color_hex';
    
    // Update the specific color parameter
    $stmt = $pdo->prepare("UPDATE soil_samples 
                          SET $color_column = ?, 
                              analyzed_at = NOW()
                          WHERE id = ?");
    
    $success = $stmt->execute([$color_hex, $sample_id]);

    if ($success) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($parameter) . ' color saved successfully',
            'parameter' => $parameter
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to save data to database']);
    }

} catch (PDOException $e) {
    error_log("Database error in save_soil_parameter.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
