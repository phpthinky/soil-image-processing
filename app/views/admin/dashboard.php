<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Add New Link</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="url" class="form-label">URL *</label>
                        <input type="url" class="form-control" id="url" name="url" 
                               value="<?php echo htmlspecialchars($post['url'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="project" <?php echo ($post['category'] ?? '') === 'project' ? 'selected' : ''; ?>>Project</option>
                            <option value="work" <?php echo ($post['category'] ?? '') === 'work' ? 'selected' : ''; ?>>Work Experience</option>
                            <option value="education" <?php echo ($post['category'] ?? '') === 'education' ? 'selected' : ''; ?>>Education</option>
                            <option value="certification" <?php echo ($post['category'] ?? '') === 'certification' ? 'selected' : ''; ?>>Certification</option>
                            <option value="personal" <?php echo ($post['category'] ?? '') === 'personal' ? 'selected' : ''; ?>>Personal</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($post['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Image (Optional)</label>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/*" onchange="previewImage(this, 'imagePreview')">
                        <small class="text-muted">Max 2MB. Allowed: JPG, PNG, GIF, WebP</small>
                        <div class="mt-2">
                            <img id="imagePreview" class="preview-img" style="display: none;">
                        </div>
                    </div>
                                        <button type="submit" class="btn btn-primary">Add Link</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Existing Links (<?php echo count($links); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($links)): ?>
                    <p class="text-muted">No links added yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>URL</th>
                                    <th>Category</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($links as $link): ?>
                                    <tr>
                                        <td>
                                            <?php if ($link['image_path']): ?>
                                                <img src="<?php echo BASE_URL . '../' . htmlspecialchars($link['image_path']); ?>" 
                                                     class="preview-img" 
                                                     alt="<?php echo htmlspecialchars($link['title']); ?>">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($link['title']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                                               target="_blank" 
                                               class="text-decoration-none">
                                                <?php echo htmlspecialchars(substr($link['url'], 0, 30)) . '...'; ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($link['category']); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo date('M d, Y', strtotime($link['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <button onclick="confirmDelete(<?php echo $link['id']; ?>)" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>