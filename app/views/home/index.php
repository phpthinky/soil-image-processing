    <div class="row mb-4">
    <div class="col-12">
        <div class="jumbotron bg-light p-5 rounded text-center">
            <h1 class="display-4 mb-3">
                Welcome to <?php echo SITE_NAME; ?>
            </h1>
            <p class="lead mb-4">
                Backend systems, data-driven applications, and real-world digital solutions.
            </p>
            <p class="text-muted mb-4">
                Explore selected projects focused on public service, education, and operational systems.
            </p>
            <a class="btn btn-primary btn-lg" href="<?php echo BASE_URL; ?>about" role="button">
                Learn More About Me
            </a>
        </div>
    </div>
</div>

<div class="row">
    <?php if (empty($links)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center">
                No projects added yet. Please check back soon.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($links as $link): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    
                    <?php if ($link['image_path']): ?>
                        <img src="<?php echo BASE_URL . '../' . htmlspecialchars($link['image_path']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($link['title']); ?>"
                             style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                             style="height: 200px;">
                            <i class="bi bi-code-slash text-white" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($link['title']); ?>
                        </h5>
                        <p class="card-text flex-grow-1">
                            <?php echo htmlspecialchars($link['description']); ?>
                        </p>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary mt-auto">
                            View Project <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                    </div>

                    <div class="card-footer text-muted small">
                        <span>
                            <i class="bi bi-calendar-event me-1"></i>
                            <?php echo date('M d, Y', strtotime($link['created_at'])); ?>
                        </span>
                        <?php if ($link['category']): ?>
                            <span class="badge bg-info float-end">
                                <?php echo htmlspecialchars($link['category']); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
