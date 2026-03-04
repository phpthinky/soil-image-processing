<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>View Message</h1>
            <a href="<?php echo BASE_URL; ?>admin/messages" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Messages
            </a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">From: <?php echo htmlspecialchars($message['name']); ?></h5>
                    <small class="text-muted">
                        <?php echo date('F j, Y, g:i a', strtotime($message['created_at'])); ?>
                    </small>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <p><strong>Email:</strong> 
                        <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>">
                            <?php echo htmlspecialchars($message['email']); ?>
                        </a>
                    </p>
                    <p><strong>IP Address:</strong> <?php echo htmlspecialchars($message['ip_address']); ?></p>
                    <?php if ($message['user_agent']): ?>
                        <p><strong>Browser:</strong> <?php echo htmlspecialchars($message['user_agent']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="border-top pt-3">
                    <h6>Message:</h6>
                    <div class="bg-light p-3 rounded">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between">
                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" 
                       class="btn btn-primary">
                        <i class="bi bi-reply"></i> Reply
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/messages?action=delete&id=<?php echo $message['id']; ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Delete this message?')">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>