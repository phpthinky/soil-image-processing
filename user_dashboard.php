<?php
require_once 'config.php';

if (!isLoggedIn()) {
  redirect('login.php');
}

// Get user's soil samples count
$stmt = $pdo->prepare("SELECT COUNT(*) as sample_count FROM soil_samples WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$sample_count = $stmt->fetch()['sample_count'];

// Get recent samples
$stmt = $pdo->prepare("SELECT * FROM soil_samples WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_samples = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="row">
  <div class="col-md-12">
    <h2>Dashboard</h2>
    <p class="lead">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-3">
    <div class="card text-white bg-primary">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4><?php echo $sample_count; ?></h4>
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
    <div class="card text-white bg-success">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div>
            <h4><?php echo date('M j, Y'); ?></h4>
            <p>Today's Date</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-calendar fa-2x"></i>
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
            <h4><?php echo ucfirst($_SESSION['user_type']); ?></h4>
            <p>User Type</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-user fa-2x"></i>
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
            <p>Municipality Agriculturist</p>
          </div>
          <div class="align-self-center">
            <i class="fas fa-seedling fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header">
        <h5>Recent Soil Samples</h5>
      </div>
      <div class="card-body">
        <?php if (count($recent_samples) > 0): ?>
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Sample Name</th>
                  <th>Date</th>
                  <th>pH Level</th>
                  <th>Fertility Score</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_samples as $sample): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($sample['sample_name']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($sample['sample_date'])); ?></td>
                    <td><?php echo $sample['ph_level']; ?></td>
                    <td>
                      <span class="badge bg-<?php 
                        if ($sample['fertility_score'] >= 80) echo 'success';
                        elseif ($sample['fertility_score'] >= 60) echo 'warning';
                        else echo 'danger';
                      ?>">
                        <?php echo $sample['fertility_score']; ?>%
                      </span>
                    </td>
                    <td>
                      <a href="results.php?sample_id=<?php echo $sample['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p>No soil samples found. <a href="analysis.php">Analyze your first sample</a>.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="card">
      <div class="card-header">
        <h5>Quick Actions</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="analysis.php" class="btn btn-success btn-lg">
            <i class="fas fa-plus-circle"></i> New Soil Analysis
          </a>
          <a href="results.php" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar"></i> View All Results
          </a>
        </div>
        
        <hr>
        
        <h6>Soil Fertility Guide</h6>
        <ul class="list-unstyled">
          <li><span class="badge bg-success">80-100%</span> High Fertility</li>
          <li><span class="badge bg-warning">60-79%</span> Medium Fertility</li>
          <li><span class="badge bg-danger">0-59%</span> Low Fertility</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
