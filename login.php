<?php
require_once 'config.php';

if (isLoggedIn()) {
  redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = $_POST['password'];
  
  if (empty($username) || empty($password)) {
    $error = 'Please enter both username and password.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $password == $user['password']) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['user_type'] = $user['user_type'];
      $_SESSION['email'] = $user['email'];
      
      if($user['user_type'] == "admin"){
        redirect('admin_dashboard.php');
      }
      else{
        redirect('dashboard.php');
      }
    } else {
      $error = 'Invalid username or password.';
    }
  }
}
?>

<?php include 'includes/header.php'; ?>

<!-- Custom CSS -->
<style>
  body {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .login-card {
    animation: fadeInDown 1s ease;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0px 8px 20px rgba(0,0,0,0.2);
  }
  @keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .card-header {
    background: #388e3c;
    color: #fff;
    text-align: center;
  }
  .form-control {
    padding-left: 40px;
  }
  .input-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
  }
  .btn-success {
    border-radius: 30px;
    transition: transform 0.2s;
  }
  .btn-success:hover {
    transform: scale(1.05);
  }
</style>
<center>
<div class="col-md-5">
  <div class="card login-card">
    <div class="card-header">
      <h4 class="mb-0"><i class="fas fa-seedling me-2"></i>Soil Analyzer Login</h4>
    </div>
      <center><img src="logo.jpg" alt="" style="width: 150px; margin-top: 10px;"></center>
    <div class="card-body">
      <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3 position-relative">
          <span class="input-icon"><i class="fas fa-user"></i></span>
          <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
        </div>
        <div class="mb-3 position-relative">
          <span class="input-icon"><i class="fas fa-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-success w-100"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
      </form>

      <!--<div class="text-center mt-3">-->
      <!--  <p class="mb-0">Don't have an account? <a href="register.php" class="text-success fw-bold">Register here</a></p>-->
      <!--</div>-->
    </div>
  </div>
</div>
</center>
<!-- Font Awesome CDN -->
<script src="https://kit.fontawesome.com/yourkitid.js" crossorigin="anonymous"></script>
