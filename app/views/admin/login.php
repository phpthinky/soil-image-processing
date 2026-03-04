<div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="text-center mb-4">Admin Login</h2>
                <!-- Security Status -->
<?php if (SECURITY_ENABLED && $attempts > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        <i class="bi bi-shield-exclamation me-2"></i>
        <strong>Security Notice:</strong> 
        <?php echo $attempts; ?> failed attempt(s). 
        <?php if ($attempts >= LOGIN_CAPTCHA_AFTER): ?>
            CAPTCHA verification required.
        <?php endif; ?>
        <?php if ($attempts >= LOGIN_BLOCK_AFTER - 1): ?>
            <br><small>Next failed attempt will temporarily block access.</small>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <!-- Show CAPTCHA if required -->
                    <?php if ($show_captcha && RECAPTCHA_ENABLED && !empty(RECAPTCHA_SITE_KEY)): ?>
                        <div class="mb-3">
                            <div class="alert alert-info">
                                <i class="bi bi-shield-check me-2"></i>
                                Security verification required.
                            </div>
                            <?php echo Security::renderRecaptcha(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
        
        <?php if ($show_captcha && RECAPTCHA_ENABLED && !empty(RECAPTCHA_SITE_KEY)): ?>
            <?php echo Security::recaptchaScript(); ?>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <?php if (SECURITY_ENABLED): ?>
                <br><small class="text-muted">
                    Security: <?php echo LOGIN_MAX_ATTEMPTS; ?> max attempts, 
                    block after <?php echo LOGIN_BLOCK_AFTER; ?>, 
                    <?php echo RECAPTCHA_ENABLED ? 'CAPTCHA enabled' : 'CAPTCHA disabled'; ?>
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>
