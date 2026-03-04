<!DOCTYPE html>
<html lang="en" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Primary Meta Tags -->
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | Democodes Online' : 'Democodes Online | PHP Development Services'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Professional PHP developer specializing in Laravel, CodeIgniter, and custom web systems for businesses and government.'; ?>">
    <meta name="keywords" content="PHP developer, Laravel, CodeIgniter, web development, system automation, backend development">
    <meta name="author" content="Harold Rita">
    
    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:url" content="<?php echo isset($page_url) ? htmlspecialchars($page_url) : BASE_URL; ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | Democodes Online' : 'Democodes Online | PHP Development Services'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Professional PHP developer specializing in Laravel, CodeIgniter, and custom web systems.'; ?>">
    <meta property="og:image" content="<?php echo BASE_URL; ?>img/logo.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Democodes Online">
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta property="twitter:domain" content="<?php echo $_SERVER['HTTP_HOST'] ?? 'democodes.online'; ?>">
    <meta property="twitter:url" content="<?php echo isset($page_url) ? htmlspecialchars($page_url) : BASE_URL; ?>">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? htmlspecialchars($page_title) . ' | Democodes Online' : 'Democodes Online'; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_description) ? htmlspecialchars($page_description) : 'Professional PHP developer services'; ?>">
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>img/logo.jpg">
    <meta name="twitter:creator" content="@phpthinky">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo isset($page_url) ? htmlspecialchars($page_url) : BASE_URL; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    
    <!-- Structured Data / Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Democodes Online",
        "url": "<?php echo BASE_URL; ?>",
        "logo": "<?php echo BASE_URL; ?>img/logo.jpg",
        "sameAs": [
            "https://github.com/phpthinky",
            "https://linkedin.com/in/harold-democodes-online"
        ],
        "description": "Professional PHP development services by Harold Rita"
    }
    </script>
    
    <style type="text/css">
        /* Professional Logo Styling */
        .navbar-brand-container {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none !important;
        }
        
        .logo-wrapper {
            position: relative;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .logo-wrapper::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(45deg, #0d6efd, #6f42c1, #198754);
            border-radius: 12px;
            z-index: 0;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .logo-wrapper::after {
            content: '';
            position: absolute;
            inset: 0;
            background: #212529;
            border-radius: 10px;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .logo-wrapper:hover::before {
            opacity: 1;
            transform: rotate(5deg);
        }
        
        .logo-wrapper:hover::after {
            background: #2b3035;
        }
        
        .logo-wrapper img {
            position: relative;
            z-index: 2;
            width: 70%;
            height: 70%;
            object-fit: contain;
            filter: brightness(1.1) contrast(1.05);
            transition: all 0.3s ease;
        }
        
        .logo-wrapper:hover img {
            transform: scale(1.05);
            filter: brightness(1.2) contrast(1.1);
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
        }
        
        .brand-name {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(90deg, #ffffff, #e0e0e0);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            line-height: 1.1;
            transition: all 0.3s ease;
        }
        
        .brand-tagline {
            font-size: 0.75rem;
            color: #adb5bd;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        
        .navbar-brand-container:hover .brand-name {
            background: linear-gradient(90deg, #ffffff, #ffffff);
            -webkit-background-clip: text;
            background-clip: text;
        }
        
        /* Professional Navigation */
        .navbar {
            padding: 0.8rem 0;
            background: linear-gradient(135deg, #212529 0%, #343a40 100%) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .nav-item {
            margin: 0 3px;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.6rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #0d6efd, #6f42c1);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .nav-link:hover::before {
            width: 80%;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.05);
            transform: translateY(-2px);
        }
        
        .nav-link.active {
            background: rgba(13, 110, 253, 0.15);
            border-left: 3px solid #0d6efd;
            font-weight: 600;
        }
        
        .nav-link.active::before {
            width: 100%;
            background: #0d6efd;
        }
        
        .nav-link i {
            font-size: 1.1em;
            transition: transform 0.3s ease;
        }
        
        .nav-link:hover i {
            transform: scale(1.1);
        }
        
        /* Mobile Menu Toggle */
        .navbar-toggler {
            border: 1px solid rgba(255,255,255,0.2);
            padding: 0.4rem 0.6rem;
            transition: all 0.3s ease;
        }
        
        .navbar-toggler:hover {
            border-color: rgba(255,255,255,0.4);
            transform: rotate(90deg);
        }
        
        .navbar-toggler:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
        }
        
        /* Mobile Navigation */
        @media (max-width: 992px) {
            .navbar-collapse {
                background: rgba(33, 37, 41, 0.98);
                backdrop-filter: blur(10px);
                padding: 1rem;
                border-radius: 10px;
                margin-top: 1rem;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            
            .nav-item {
                margin: 5px 0;
            }
            
            .nav-link {
                border-radius: 6px;
                padding: 0.8rem 1rem !important;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-wrapper {
                width: 50px;
                height: 50px;
            }
            
            .brand-name {
                font-size: 1.3rem;
            }
            
            .brand-tagline {
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand-container {
                gap: 8px;
            }
            
            .brand-name {
                font-size: 1.1rem;
            }
            
            .brand-tagline {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a href="<?php echo BASE_URL; ?>about" class="navbar-brand-container" title="Visit About Me page">
                <div class="logo-wrapper">
                    <img src="<?php echo BASE_URL; ?>img/logo.jpg" 
                         alt="Democodes Online Logo - Professional PHP Development Services">
                </div>
                <div class="brand-text">
                    <span class="brand-name">Democodes Online</span>
                    <span class="brand-tagline">PHP Development Services</span>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#navbarNav"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page_title ?? '') == 'Home' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>index"
                           title="Home Page">
                           <i class="bi bi-house-door me-2"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page_title ?? '') == 'About' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>about"
                           title="About Harold Rita - PHP Developer">
                           <i class="bi bi-person-badge me-2"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page_title ?? '') == 'Portfolio' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>portfolio"
                           title="View Project Portfolio">
                           <i class="bi bi-briefcase me-2"></i>Portfolio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($page_title ?? '') == 'Contact' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>contact"
                           title="Contact for PHP Development Services">
                           <i class="bi bi-envelope-paper me-2"></i>Contact
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-2"></i>Account
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>admin/dashboard">
                                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>admin/logout">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light px-3 ms-2 <?php echo ($page_title ?? '') == 'Login' ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>admin/login"
                               title="Admin Login">
                               <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Optional: Breadcrumb -->
    <?php if (isset($breadcrumbs)): ?>
    <nav aria-label="breadcrumb" class="bg-light border-bottom">
        <div class="container">
            <ol class="breadcrumb mb-0 py-2">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <?php if (isset($crumb['url'])): ?>
                        <li class="breadcrumb-item"><a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['label']; ?></a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $crumb['label']; ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </div>
    </nav>
    <?php endif; ?>
    
    <main class="py-4">
        <div class="container-fluid">