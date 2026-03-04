<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | <?php echo SITE_NAME; ?></title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .preview-img { max-width: 100px; max-height: 100px; object-fit: cover; }
        .profile-avatar {
            width: 100px; height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>admin/dashboard">Admin Dashboard</a>
            <div>
                <a href="<?php echo BASE_URL; ?>index" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-house"></i> View Site
                </a>
                <a href="<?php echo BASE_URL; ?>admin/dashboard" class="btn btn-outline-light btn-sm ms-2">
                    <i class="bi bi-grid"></i> Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>admin/profile" class="btn btn-outline-light btn-sm ms-2">
                    <i class="bi bi-person-circle"></i> Profile
                </a>
                <!-- In the navbar, add this after Profile link: -->
<a href="<?php echo BASE_URL; ?>admin/messages" class="btn btn-outline-light btn-sm ms-2 position-relative">
    <i class="bi bi-envelope"></i> Messages
    <?php 
    // Show unread count badge
    $messageModel = new Message();
    $unreadCount = $messageModel->getUnreadCount();
    if ($unreadCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?php echo $unreadCount; ?>
            <span class="visually-hidden">unread messages</span>
        </span>
    <?php endif; ?>
</a>
                <a href="<?php echo BASE_URL; ?>admin/logout" class="btn btn-outline-light btn-sm ms-2">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">