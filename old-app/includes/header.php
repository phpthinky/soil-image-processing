<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soil Fertility Analyzer — OMA</title>

    <!-- Bootstrap 5 -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ── Global ───────────────────────────────────────────── */
        body {
            background-color: #f4f6f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ── Top navbar ───────────────────────────────────────── */
        .navbar-brand img { height: 38px; margin-right: 8px; }

        .navbar {
            background: linear-gradient(90deg, #2e7d32, #388e3c) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,.25);
        }
        .navbar .nav-link          { color: rgba(255,255,255,.85) !important; }
        .navbar .nav-link:hover,
        .navbar .nav-link.active   { color: #fff !important; }
        .navbar .dropdown-item:hover { background-color: #e8f5e9; }

        /* Active nav-link underline */
        .navbar .nav-link.active {
            border-bottom: 2px solid #a5d6a7;
        }

        /* ── Sidebar / main layout ───────────────────────────── */
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #1b5e20;
            padding-top: 1rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .6rem 1.2rem;
            border-radius: 4px;
            margin: 2px 8px;
            transition: background .2s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.15);
            color: #fff;
        }
        .sidebar .nav-link i { width: 20px; text-align: center; margin-right: 8px; }

        .main-content {
            padding: 1.5rem 2rem;
        }

        /* ── Cards ────────────────────────────────────────────── */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            border-radius: 10px;
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }

        /* ── Buttons ─────────────────────────────────────────── */
        .btn { border-radius: 6px; }
        .btn-success { background-color: #388e3c; border-color: #2e7d32; }
        .btn-success:hover { background-color: #2e7d32; }

        /* ── Tables ──────────────────────────────────────────── */
        .table thead th { font-weight: 600; font-size: .875rem; }

        /* ── Alerts ──────────────────────────────────────────── */
        .alert { border-radius: 8px; }
    </style>
</head>
<body>

<!-- ── TOP NAVBAR ──────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>">
            <?php if (file_exists(__DIR__ . '/../logo.jpg')): ?>
                <img src="logo.jpg" alt="Logo">
            <?php endif; ?>
            <span class="fw-bold">Soil Fertility Analyzer</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (isLoggedIn()): ?>
            <!-- Left nav links -->
            <ul class="navbar-nav me-auto">
                <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>"
                           href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>"
                           href="users.php">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'user_dashboard.php' ? 'active' : ''; ?>"
                           href="user_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'analysis.php' ? 'active' : ''; ?>"
                       href="analysis.php">
                        <i class="fas fa-flask me-1"></i>New Analysis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'results.php' ? 'active' : ''; ?>"
                       href="results.php">
                        <i class="fas fa-chart-bar me-1"></i>Results
                    </a>
                </li>
            </ul>

            <!-- Right: user dropdown -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#"
                       id="userDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <span class="badge bg-light text-dark ms-1" style="font-size:.7rem;">
                            <?php echo ucfirst($_SESSION['user_type']); ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li>
                            <span class="dropdown-item-text text-muted small">
                                <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── PAGE BODY WRAPPER ───────────────────────────────────────────── -->
<div class="container-fluid">
    <div class="row">
        <?php if (isLoggedIn()): ?>
        <!-- Sidebar -->
        <nav class="col-md-2 d-none d-md-block sidebar">
            <div class="pt-2">
                <ul class="nav flex-column">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : ''; ?>"
                               href="admin_dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>"
                               href="users.php">
                                <i class="fas fa-users"></i>Manage Users
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'user_dashboard.php' ? 'active' : ''; ?>"
                               href="user_dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'analysis.php' ? 'active' : ''; ?>"
                           href="analysis.php">
                            <i class="fas fa-flask"></i>New Analysis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'results.php' ? 'active' : ''; ?>"
                           href="results.php">
                            <i class="fas fa-chart-bar"></i>Results
                        </a>
                    </li>

                    <li class="mt-4">
                        <hr style="border-color:rgba(255,255,255,.2);">
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content area -->
        <main class="col-md-10 ms-sm-auto main-content">
        <?php else: ?>
        <!-- Full-width for login/register pages -->
        <main class="col-12">
        <?php endif; ?>
