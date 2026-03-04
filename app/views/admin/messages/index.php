<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Contact Messages</h1>
            <span class="badge bg-danger"><?php echo $unreadCount; ?> Unread</span>
        </div>
        
        <?php if (isset($message) && $message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($messages)): ?>
            <div class="alert alert-info">
                No messages yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="20%">Name</th>
                            <th width="20%">Email</th>
                            <th width="35%">Message Preview</th>
                            <th width="10%">Status</th>
                            <th width="10%">Date</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?php echo $msg['is_read'] ? '' : 'table-active'; ?>">
                                <td><?php echo $msg['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['name']); ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>">
                                        <?php echo htmlspecialchars($msg['email']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?>
                                    <?php if (strlen($msg['message']) > 100): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($msg['is_read']): ?>
                                        <span class="badge bg-success">Read</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Unread</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>admin/messages/view/<?php echo $msg['id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>admin/messages?action=delete&id=<?php echo $msg['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Delete this message?')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="<?php echo BASE_URL; ?>admin/dashboard" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>