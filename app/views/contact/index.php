<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        
        <!-- Header Section -->
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bold text-dark mb-3">Get In Touch</h1>
            <p class="lead text-muted mb-4">Interested in discussing a project, collaboration, or opportunity? I'd love to hear from you.</p>
            <div class="border-top border-bottom py-3 mb-4">
                <div class="row text-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="rounded-circle bg-light p-3 me-3">
                                <i class="bi bi-envelope fs-4 text-primary"></i>
                            </div>
                            <div class="text-start">
                                <small class="text-muted d-block">Email</small>
                                <strong><?php echo htmlspecialchars($admin_email); ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="rounded-circle bg-light p-3 me-3">
                                <i class="bi bi-linkedin fs-4 text-primary"></i>
                            </div>
                            <div class="text-start">
                                <small class="text-muted d-block">LinkedIn</small>
                                <strong>@harold-democodes-online</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="rounded-circle bg-light p-3 me-3">
                                <i class="bi bi-github fs-4 text-primary"></i>
                            </div>
                            <div class="text-start">
                                <small class="text-muted d-block">GitHub</small>
                                <strong>@phpthinky</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                <div>
                    <strong>Message Sent Successfully!</strong>
                    <p class="mb-0">Thank you for your message. I'll get back to you within 24-48 hours.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div>
                    <strong>Unable to Send Message</strong>
                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <h3 class="card-title mb-0">
                            <i class="bi bi-send me-2 text-primary"></i>Send a Message
                        </h3>
                        <p class="text-muted mb-0">Fill out the form below and I'll respond as soon as possible.</p>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="" id="contactForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label fw-semibold">
                                        <i class="bi bi-person me-1 text-muted"></i>Full Name
                                    </label>
                                    <input type="text" 
                                           class="form-control <?php echo isset($validation_errors['name']) ? 'is-invalid' : ''; ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?php echo htmlspecialchars($post['name'] ?? ''); ?>" 
                                           required
                                           placeholder="Your full name">
                                    <?php if (isset($validation_errors['name'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <?php echo htmlspecialchars($validation_errors['name']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="bi bi-envelope me-1 text-muted"></i>Email Address
                                    </label>
                                    <input type="email" 
                                           class="form-control <?php echo isset($validation_errors['email']) ? 'is-invalid' : ''; ?>" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($post['email'] ?? ''); ?>" 
                                           required
                                           placeholder="your.email@example.com">
                                    <?php if (isset($validation_errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <?php echo htmlspecialchars($validation_errors['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-12">
                                    <label for="subject" class="form-label fw-semibold">
                                        <i class="bi bi-tag me-1 text-muted"></i>Subject
                                    </label>
                                    <select class="form-select <?php echo isset($validation_errors['subject']) ? 'is-invalid' : ''; ?>" 
                                            id="subject" 
                                            name="subject" 
                                            required>
                                        <option value="" disabled <?php echo !isset($post['subject']) ? 'selected' : ''; ?>>Select a topic</option>
                                        <option value="project" <?php echo ($post['subject'] ?? '') === 'project' ? 'selected' : ''; ?>>Project Inquiry</option>
                                        <option value="collaboration" <?php echo ($post['subject'] ?? '') === 'collaboration' ? 'selected' : ''; ?>>Collaboration Opportunity</option>
                                        <option value="employment" <?php echo ($post['subject'] ?? '') === 'employment' ? 'selected' : ''; ?>>Employment Opportunity</option>
                                        <option value="technical" <?php echo ($post['subject'] ?? '') === 'technical' ? 'selected' : ''; ?>>Technical Consultation</option>
                                        <option value="other" <?php echo ($post['subject'] ?? '') === 'other' ? 'selected' : ''; ?>>Other Inquiry</option>
                                    </select>
                                    <?php if (isset($validation_errors['subject'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <?php echo htmlspecialchars($validation_errors['subject']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-12">
                                    <label for="message" class="form-label fw-semibold">
                                        <i class="bi bi-chat-left-text me-1 text-muted"></i>Message
                                    </label>
                                    <textarea class="form-control <?php echo isset($validation_errors['message']) ? 'is-invalid' : ''; ?>" 
                                              id="message" 
                                              name="message" 
                                              rows="6" 
                                              required
                                              placeholder="Tell me about your project, timeline, and requirements..."><?php echo htmlspecialchars($post['message'] ?? ''); ?></textarea>
                                    <div class="form-text">Please include relevant details such as project scope, timeline, and budget if applicable.</div>
                                    <?php if (isset($validation_errors['message'])): ?>
                                        <div class="invalid-feedback">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <?php echo htmlspecialchars($validation_errors['message']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- reCAPTCHA if enabled -->
                                <?php if (RECAPTCHA_ENABLED && !empty(RECAPTCHA_SITE_KEY)): ?>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">
                                                <i class="bi bi-shield-check me-1 text-muted"></i>Security Verification
                                            </label>
                                            <?php echo Security::renderRecaptcha(); ?>
                                            <?php if (isset($validation_errors['recaptcha'])): ?>
                                                <div class="invalid-feedback d-block">
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                    <?php echo htmlspecialchars($validation_errors['recaptcha']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="col-12">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">
                                            <i class="bi bi-send me-2"></i>Send Message
                                        </button>
                                    </div>
                                    <p class="text-muted small text-center mt-3">
                                        <i class="bi bi-clock me-1"></i>
                                        Typical response time: 24-48 hours
                                    </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information & Services -->
            <div class="col-lg-4">
                <!-- Services Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-gear me-2 text-primary"></i>Services I Offer
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <div>
                                    <strong>Custom Web Development</strong>
                                    <p class="text-muted small mb-0">PHP, Laravel, CodeIgniter systems</p>
                                </div>
                            </li>
                            <li class="mb-3 d-flex">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <div>
                                    <strong>System Analysis & Consulting</strong>
                                    <p class="text-muted small mb-0">Process automation solutions</p>
                                </div>
                            </li>
                            <li class="mb-3 d-flex">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <div>
                                    <strong>Legacy System Modernization</strong>
                                    <p class="text-muted small mb-0">Upgrade and optimization</p>
                                </div>
                            </li>
                            <li class="d-flex">
                                <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                <div>
                                    <strong>Technical Documentation</strong>
                                    <p class="text-muted small mb-0">System manuals and guides</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Availability Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-calendar-check me-2 text-primary"></i>Availability
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="badge bg-success bg-opacity-10 text-success p-2 rounded me-3">
                                    <i class="bi bi-check-lg"></i>
                                </div>
                                <div>
                                    <strong class="d-block">Currently Available</strong>
                                    <small class="text-muted">For new projects</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <strong class="d-block mb-2"><i class="bi bi-clock me-1"></i>Response Time</strong>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 90%" 
                                     aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Initial</small>
                                <small class="text-muted">24-48 hours</small>
                            </div>
                        </div>
                        
                        <div>
                            <strong class="d-block mb-2"><i class="bi bi-geo-alt me-1"></i>Location</strong>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-pin-map text-primary me-2"></i>
                                <div>
                                    <p class="mb-0">Novaliches, Quezon City</p>
                                    <small class="text-muted">Open to remote or relocation</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="d-grid gap-2 mt-4">
                    <a href="https://linkedin.com/in/harold-democodes-online" 
                       target="_blank" 
                       class="btn btn-outline-primary d-flex align-items-center justify-content-center">
                        <i class="bi bi-linkedin me-2"></i>View LinkedIn Profile
                    </a>
                    <a href="https://github.com/phpthinky" 
                       target="_blank" 
                       class="btn btn-outline-dark d-flex align-items-center justify-content-center">
                        <i class="bi bi-github me-2"></i>Browse GitHub Portfolio
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Additional Info -->
        <div class="card border-0 bg-light mt-5">
            <div class="card-body text-center p-4">
                <h5 class="mb-3">What Happens After You Send Your Message?</h5>
                <div class="row">
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="bg-white rounded-circle p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-envelope-open fs-3 text-primary"></i>
                        </div>
                        <h6>1. Initial Review</h6>
                        <p class="small text-muted mb-0">Message received and categorized</p>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="bg-white rounded-circle p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-chat-dots fs-3 text-primary"></i>
                        </div>
                        <h6>2. Response Planning</h6>
                        <p class="small text-muted mb-0">Detailed response prepared</p>
                    </div>
                    <div class="col-md-3 mb-3 mb-md-0">
                        <div class="bg-white rounded-circle p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-calendar-event fs-3 text-primary"></i>
                        </div>
                        <h6>3. Follow-up Scheduled</h6>
                        <p class="small text-muted mb-0">Discussion call if needed</p>
                    </div>
                    <div class="col-md-3">
                        <div class="bg-white rounded-circle p-3 mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="bi bi-handshake fs-3 text-primary"></i>
                        </div>
                        <h6>4. Next Steps</h6>
                        <p class="small text-muted mb-0">Project proposal or consultation</p>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php if (RECAPTCHA_ENABLED && !empty(RECAPTCHA_SITE_KEY)): ?>
    <?php echo Security::recaptchaScript(); ?>
<?php endif; ?>

<style>
    .form-control, .form-select {
        border: 1px solid #dee2e6;
        padding: 0.75rem 1rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }
    
    .card {
        border-radius: 12px;
    }
    
    .progress {
        border-radius: 4px;
    }
    
    .alert {
        border-radius: 10px;
        border: none;
    }
</style>