<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Soil Fertility Analyzer') — OMA</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body { background-color: #f4f6f4; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: linear-gradient(90deg, #2e7d32, #388e3c) !important; box-shadow: 0 2px 6px rgba(0,0,0,.25); }
        .navbar .nav-link { color: rgba(255,255,255,.85) !important; }
        .navbar .nav-link:hover, .navbar .nav-link.active { color: #fff !important; }
        .navbar .nav-link.active { border-bottom: 2px solid #a5d6a7; }
        .navbar-brand img { height: 38px; margin-right: 8px; }
        .sidebar { min-height: calc(100vh - 56px); background: #1b5e20; padding-top: 1rem; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); padding: .6rem 1.2rem; border-radius: 4px; margin: 2px 8px; transition: background .2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,.15); color: #fff; }
        .sidebar .nav-link i { width: 20px; text-align: center; margin-right: 8px; }
        .main-content { padding: 1.5rem 2rem; }
        .card { border: none; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: 10px; }
        .card-header { border-radius: 10px 10px 0 0 !important; font-weight: 600; }
        .btn { border-radius: 6px; }
        .btn-success { background-color: #388e3c; border-color: #2e7d32; }
        .btn-success:hover { background-color: #2e7d32; }
        .table thead th { font-weight: 600; font-size: .875rem; }
        .alert { border-radius: 8px; }
    </style>
    @yield('styles')
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="{{ auth()->user()?->isAdmin() ? route('admin.dashboard') : route('dashboard') }}">
            <span class="fw-bold">Soil Fertility Analyzer</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            @auth
            <ul class="navbar-nav me-auto">
                @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                            <i class="fas fa-users me-1"></i>Users
                        </a>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('samples.create') ? 'active' : '' }}" href="{{ route('samples.create') }}">
                        <i class="fas fa-flask me-1"></i>New Analysis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('samples.index') ? 'active' : '' }}" href="{{ route('samples.index') }}">
                        <i class="fas fa-chart-bar me-1"></i>Results
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1"></i>
                        {{ auth()->user()->username }}
                        <span class="badge bg-light text-dark ms-1" style="font-size:.7rem;">{{ ucfirst(auth()->user()->user_type) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
            @endauth
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        @auth
        <nav class="col-md-2 d-none d-md-block sidebar">
            <div class="pt-2">
                <ul class="nav flex-column">
                    @if(auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users') }}">
                                <i class="fas fa-users"></i>Manage Users
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('samples.create') ? 'active' : '' }}" href="{{ route('samples.create') }}">
                            <i class="fas fa-flask"></i>New Analysis
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('samples.index') ? 'active' : '' }}" href="{{ route('samples.index') }}">
                            <i class="fas fa-chart-bar"></i>Results
                        </a>
                    </li>
                    <li class="mt-4"><hr style="border-color:rgba(255,255,255,.2);"></li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link text-danger border-0 bg-transparent w-100 text-start">
                                <i class="fas fa-sign-out-alt"></i>Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>
        <main class="col-md-10 ms-sm-auto main-content">
        @else
        <main class="col-12">
        @endauth

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mt-3">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')

        </main>
    </div>
</div>

<footer class="text-center py-3 mt-4" style="background:#2e7d32; color:rgba(255,255,255,.75); font-size:.85rem;">
    <div class="container-fluid">
        <span>&copy; {{ date('Y') }} <strong>Soil Fertility Analyzer</strong> &mdash; Office of the Municipal Agriculturist (OMA)</span>
        <span class="ms-4 text-white-50">v2.0 (Laravel 11)</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
