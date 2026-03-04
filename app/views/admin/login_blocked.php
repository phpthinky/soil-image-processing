<div class="row justify-content-center min-vh-100 align-items-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow border-danger">
            <div class="card-body p-4 text-center">
                <div class="mb-4">
                    <i class="bi bi-shield-lock text-danger" style="font-size: 3rem;"></i>
                </div>
                
                <h3 class="text-danger mb-3">Login Temporarily Blocked</h3>
                
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Too many failed login attempts detected.
                </div>
                
                <?php if (isset($block_data['remaining'])): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-clock-history me-2"></i>
                        <strong>Please wait <?php echo gmdate("i", $block_data['remaining']); ?> minutes</strong>
                        before trying again.
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-house me-1"></i> Return to Home
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/login" class="btn btn-outline-primary ms-2">
                        <i class="bi bi-arrow-clockwise me-1"></i> Try Again
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>