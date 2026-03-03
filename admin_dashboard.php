<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
  redirect('login.php');
}

// Get statistics
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$samples_count = $pdo->query("SELECT COUNT(*) FROM soil_samples")->fetchColumn();
$crops_count = $pdo->query("SELECT COUNT(*) FROM crops")->fetchColumn();

// Get recent activities
$recent_samples = $pdo->query("SELECT s.*, u.username 
               FROM soil_samples s 
               JOIN users u ON s.user_id = u.id 
               ORDER BY s.created_at DESC 
               LIMIT 5")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="row">
  <div class="col-md-12">
    <h2>Admin Dashboard</h2>
    <p class="lead">System overview and management.</p>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-3">
    <div class="card text-white bg-primary">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4><?php echo $users_count; ?></h4>
            <p>Total Users</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-users fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card text-white bg-success">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4><?php echo $samples_count; ?></h4>
            <p>Soil Samples</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-vial fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card text-white bg-warning">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4><?php echo $crops_count; ?></h4>
            <p>Crop Types</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-seedling fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-md-3">
    <div class="card text-white bg-info">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4>OMA</h4>
            <p>Administration</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-cogs fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5>Recent Soil Samples</h5>
      </div>
      <div class="card-body">
        <?php if (count($recent_samples) > 0): ?>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Sample</th>
                  <th>User</th>
                  <th>Date</th>
                  <th>Fertility</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_samples as $sample): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($sample['sample_name']); ?></td>
                    <td><?php echo htmlspecialchars($sample['username']); ?></td>
                    <td><?php echo date('M j', strtotime($sample['created_at'])); ?></td>
                    <td>
                      <span class="badge bg-<?php 
                        if ($sample['fertility_score'] >= 80) echo 'success';
                        elseif ($sample['fertility_score'] >= 60) echo 'warning';
                        else echo 'danger';
                      ?>">
                        <?php echo $sample['fertility_score']; ?>%
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p>No soil samples found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">
        <h5>Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="users.php" class="btn btn-outline-primary">
            <i class="fas fa-users"></i> Manage Users
          </a>
          <a href="analysis.php" class="btn btn-outline-success">
            <i class="fas fa-flask"></i> New Soil Analysis
          </a>
          <button class="btn btn-outline-warning">
            <i class="fas fa-seedling"></i> Crop Database
          </button>
          <button class="btn btn-outline-info">
            <i class="fas fa-chart-bar"></i> Reports
          </button>
        </div>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header">
        <h5>System Information</h5>
      </div>
      <div class="card-body">
        <table class="table table-sm">
          <tr>
            <td><strong>System Version:</strong></td>
            <td>Soil Fertility Analyzer 1.0</td>
          </tr>
          <tr>
            <td><strong>Last Update:</strong></td>
            <td><?php echo date('F j, Y'); ?></td>
          </tr>
          <tr>
            <td><strong>Sensor Type:</strong></td>
            <td>HD WEB CAM</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
