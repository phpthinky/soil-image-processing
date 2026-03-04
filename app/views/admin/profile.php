<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="profile-section text-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; padding: 30px; margin-bottom: 30px;">
            <div class="profile-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h2><?php echo htmlspecialchars($user['username']); ?></h2>
            <p class="mb-0">Administrator</p>
            <small>Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></small>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
                    <i class="bi bi-person me-1"></i> Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button">
                    <i class="bi bi-key me-1"></i> Change Password
                </button>
            </li>
        </ul>
        
        <!-- Tab Content -->
        <div class="tab-content" id="profileTabContent">
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Profile Information</h5>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       disabled readonly>
                                <small class="text-muted">Username cannot be changed.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                       placeholder="Enter your email address">
                                <small class="text-muted">This email will be used for contact form notifications.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Account Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Account Created</th>
                                <td><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>User ID</th>
                                <td><?php echo $user['id']; ?></td>
                            </tr>
                            <tr>
                                <th>Role</th>
                                <td><span class="badge bg-primary">Administrator</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Change Password Tab -->
            <div class="tab-pane fade" id="password" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Change Password</h5>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required>
                                <small class="text-muted">Password must be at least 6 characters long.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-container" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                After changing your password, you will need to login again.
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key me-1"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Password Requirements</h5>
                        <ul class="mb-0">
                            <li>At least 6 characters long</li>
                            <li>Use a mix of letters, numbers, and symbols</li>
                            <li>Avoid using common words or personal information</li>
                            <li>Consider using a password manager</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>admin/dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>