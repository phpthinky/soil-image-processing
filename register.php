<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed_password = $password; // ⚠️ Recommend using password_hash in production
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $user_type])) {
                $success = 'Registration successful! You can now <a href="login.php" class="text-decoration-none fw-bold text-success">login</a>.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
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
    .register-card {
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
<div class="col-md-6 col-lg-5">
    <div class="card register-card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Soil Analyzer Registration</h4>
        </div>
        <center><img src="logo.jpg" alt="Logo" style="width: 120px; margin-top: 10px;"></center>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="mb-3 position-relative">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="mb-3 position-relative">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                    </div>
                    <div class="mb-3 position-relative">
                        <span class="input-icon"><i class="fas fa-user-tag"></i></span>
                        <select class="form-control" id="user_type" name="user_type" required>
                            <option value="farmer">Farmer</option>
                            <option value="professional">Agricultural Professional</option>
                        </select>
                    </div>
                    <div class="mb-3 position-relative">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="mb-3 position-relative">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-user-check me-2"></i>Register</button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">Already have an account? <a href="login.php" class="text-success fw-bold">Login here</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</center>

<!-- Font Awesome CDN -->
<script src="https://kit.fontawesome.com/yourkitid.js" crossorigin="anonymous"></script>
