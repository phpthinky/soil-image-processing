<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sample_name = trim($_POST['sample_name']);
    $location = trim($_POST['location']);
    $sample_date = $_POST['sample_date'];
    $farmer_name = trim($_POST['farmer_name']);
    $address = trim($_POST['address']);
    $date_tested = $_POST['date_tested'];

    // Validation
    if (empty($sample_name) || empty($sample_date) || empty($farmer_name) || empty($date_tested)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Save to database
        $stmt = $pdo->prepare("INSERT INTO soil_samples 
            (user_id, sample_name, location, sample_date, farmer_name, address, date_tested, color_hex) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt->execute([
            $_SESSION['user_id'], 
            $sample_name, 
            $location, 
            $sample_date, 
            $farmer_name, 
            $address, 
            $date_tested, 
            '#8B4513'
        ])) {
            $sample_id = $pdo->lastInsertId();
            $success = 'Soil sample added successfully! The sample is now ready for webcam-based analysis.';
            
            redirect("results.php");
        } else {
            $error = 'Failed to save soil sample. Please try again.';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>Add Soil Sample</h2>
        <p class="lead">Add a new soil sample for webcam-based soil nutrient analysis.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Soil Sample Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="sampleForm">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="farmer_name" class="form-label">Name of Farmer *</label>
                                <input type="text" class="form-control" id="farmer_name" name="farmer_name" placeholder="Enter Farmer's Name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="e.g., Poblacion I, San Teodoro" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Farm Location</label>
                        <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Field A, North Section">
                        <div class="form-text">Optional: Specify where the sample was taken from</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_tested" class="form-label">Date Tested *</label>
                        <input type="date" class="form-control" id="date_tested" name="date_tested" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sample_name" class="form-label">Sample Name *</label>
                                <input type="text" class="form-control" id="sample_name" name="sample_name" required>
                                <div class="form-text">Give a descriptive name for your soil sample</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="sample_date" class="form-label">Date Received *</label>
                                <input type="date" class="form-control" id="sample_date" name="sample_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> After adding the soil sample, you can analyze it using the system's webcam-based image analysis to determine soil pH, Nitrogen (N), Phosphorus (P), and Potassium (K) levels.
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-plus-circle"></i> Add Soil Sample
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Sample Collection Guide</h5>
            </div>
            <div class="card-body">
                <p><strong>Proper soil sampling technique:</strong></p>
                <ol class="small">
                    <li>Collect samples from multiple locations in the field</li>
                    <li>Take samples at 6-8 inches depth</li>
                    <li>Mix samples thoroughly in a clean container</li>
                    <li>Allow soil to air dry before analysis</li>
                    <li>Label samples clearly with location and date</li>
                </ol>
                
                <div class="mt-3">
                    <strong>Next Steps:</strong>
                    <ul class="small mt-2">
                        <li>After adding the sample, use the webcam to capture the soil image</li>
                        <li>The system will analyze soil pH, Nitrogen (N), Phosphorus (P), and Potassium (K)</li>
                        <li>Receive crop recommendations based on the NPK and pH analysis</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Webcam Soil Analysis Information</h5>
            </div>
            <div class="card-body">
                <p class="small">
                    The system webcam captures soil images which are processed using image analysis 
                    to estimate:
                </p>
                <ul class="small">
                    <li>Soil pH level</li>
                    <li>Nitrogen (N) content</li>
                    <li>Phosphorus (P) content</li>
                    <li>Potassium (K) content</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('sampleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = ['sample_name', 'sample_date', 'farmer_name', 'address', 'date_tested'];
            let valid = true;

            requiredFields.forEach(id => {
                const field = document.getElementById(id);
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
