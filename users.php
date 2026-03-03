<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $user_type = $_POST['user_type'];
        
        // Validate input
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        if (!in_array($user_type, ['farmer', 'professional', 'admin'])) {
            $errors[] = "Invalid user type.";
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        }
        
        if (empty($errors)) {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password, $user_type])) {
                $_SESSION['success_message'] = "User added successfully!";
                redirect('users.php');
            } else {
                $errors[] = "Failed to add user. Please try again.";
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode("<br>", $errors);
        }
    }
    
    if (isset($_POST['edit_user'])) {
        // Edit existing user
        $user_id = intval($_POST['user_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $user_type = $_POST['user_type'];
        $change_password = isset($_POST['change_password']);
        
        // Validate input
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required.";
        }
        
        if (!in_array($user_type, ['farmer', 'professional', 'admin'])) {
            $errors[] = "Invalid user type.";
        }
        
        // Check if username or email already exists (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        }
        
        if ($change_password) {
            $password = $_POST['password'];
            if (empty($password) || strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters long.";
            }
        }
        
        if (empty($errors)) {
            if ($change_password) {
                // Update user with new password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ?, user_type = ? WHERE id = ?");
                $success = $stmt->execute([$username, $email, $hashed_password, $user_type, $user_id]);
            } else {
                // Update user without changing password
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, user_type = ? WHERE id = ?");
                $success = $stmt->execute([$username, $email, $user_type, $user_id]);
            }
            
            if ($success) {
                $_SESSION['success_message'] = "User updated successfully!";
                redirect('users.php');
            } else {
                $errors[] = "Failed to update user. Please try again.";
            }
        }
        
        if (!empty($errors)) {
            $_SESSION['error_message'] = implode("<br>", $errors);
        }
    }
}

// Handle user deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Prevent admin from deleting themselves
    if ($delete_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        $_SESSION['success_message'] = "User deleted successfully!";
        redirect('users.php');
    } else {
        $_SESSION['error_message'] = "You cannot delete your own account.";
        redirect('users.php');
    }
}

// Get user data for editing
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch();
    
    if (!$edit_user) {
        $_SESSION['error_message'] = "User not found.";
        redirect('users.php');
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <h2>User Management</h2>
        <p class="lead">Manage system users and their permissions.</p>
    </div>
</div>

<!-- Display messages -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; ?>
        <?php unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; ?>
        <?php unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row mt-4">
    <!-- Add/Edit User Form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="user_type" class="form-label">User Type</label>
                        <select class="form-control" id="user_type" name="user_type" required>
                            <option value="farmer" <?php echo ($edit_user && $edit_user['user_type'] == 'farmer') ? 'selected' : ''; ?>>Farmer</option>
                            <option value="professional" <?php echo ($edit_user && $edit_user['user_type'] == 'professional') ? 'selected' : ''; ?>>Professional</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <?php if ($edit_user): ?>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="change_password" name="change_password">
                            <label class="form-check-label" for="change_password">Change Password</label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3" id="password_field" style="<?php echo $edit_user ? 'display: none;' : ''; ?>">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               <?php echo !$edit_user ? 'required' : ''; ?> minlength="6">
                        <?php if ($edit_user): ?>
                            <div class="form-text">Leave blank to keep current password.</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php if ($edit_user): ?>
                            <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                        <?php else: ?>
                            <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Users List -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>All Users</h5>
            </div>
            <div class="card-body">
                <?php if (count($users) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                switch($user['user_type']) {
                                                    case 'admin': echo 'danger'; break;
                                                    case 'professional': echo 'warning'; break;
                                                    default: echo 'success'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($user['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="users.php?edit_id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-outline-primary">Edit</a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete_id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this user?')">
                                                        Delete
                                                    </a>
                                                <?php else: ?>
                                                    <span class="btn btn-outline-secondary disabled">Current user</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No users found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide password field based on checkbox
document.addEventListener('DOMContentLoaded', function() {
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordField = document.getElementById('password_field');
    
    if (changePasswordCheckbox && passwordField) {
        changePasswordCheckbox.addEventListener('change', function() {
            if (this.checked) {
                passwordField.style.display = 'block';
                document.getElementById('password').required = true;
            } else {
                passwordField.style.display = 'none';
                document.getElementById('password').required = false;
                document.getElementById('password').value = '';
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
