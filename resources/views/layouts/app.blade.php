<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Soil Fertility Analyzer') }} — OMA</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* ── Top navbar ─────────────────────────────────────────── */
        .navbar-brand img { height: 38px; margin-right: 8px; }
        .navbar {
            background: linear-gradient(90deg, #2e7d32, #388e3c) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,.25);
        }
        .navbar .nav-link          { color: rgba(255,255,255,.85) !important; }
        .navbar .nav-link:hover,
        .navbar .nav-link.active   { color: #fff !important; }
        .navbar .dropdown-item:hover { background-color: #e8f5e9; }
        .navbar .nav-link.active {
            border-bottom: 2px solid #a5d6a7;
        }
        .navbar-brand { color: #fff !important; font-weight: 600; letter-spacing: .3px; }
        .navbar-toggler { border-color: rgba(255,255,255,.4); }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,.75)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #1b5e20;
            padding-top: 1rem;
            width: 220px;
            flex-shrink: 0;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.75);
            padding: .6rem 1.2rem;
            border-radius: 4px;
            margin: 2px 8px;
            transition: background .2s;
            font-size: .9rem;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,.15);
            color: #fff;
        }
        .sidebar .nav-link i { width: 20px; text-align: center; margin-right: 8px; }
        .sidebar-section {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255,255,255,.4);
            padding: .8rem 1.4rem .2rem;
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,.1);
            margin: .4rem 1rem;
        }

        /* ── Main content ────────────────────────────────────────── */
        .app-body { display: flex; }
        .main-content {
            flex: 1;
            padding: 1.5rem 2rem;
            min-width: 0;
        }

        /* ── Cards ───────────────────────────────────────────────── */
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            border-radius: 10px;
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }

        /* ── Buttons ─────────────────────────────────────────────── */
        .btn { border-radius: 6px; }
        .btn-success { background-color: #388e3c; border-color: #2e7d32; }
        .btn-success:hover { background-color: #2e7d32; }

        /* ── Tables ──────────────────────────────────────────────── */
        .table thead th { font-weight: 600; font-size: .875rem; }

        /* ── Alerts ──────────────────────────────────────────────── */
        .alert { border-radius: 8px; }

        @yield('styles')
    </style>
</head>
<body>

    {{-- ── Top Navbar ─────────────────────────────────────────── --}}
    <nav class="navbar navbar-expand-md">
        <div class="container-fluid px-3">
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                @if(file_exists(public_path('logo.jpg')))
                    <img src="{{ asset('logo.jpg') }}" alt="Logo">
                @endif
                {{ config('app.name', 'Soil Fertility Analyzer 2.0') }}
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">

                {{-- Left: main nav links (authenticated) --}}
                <ul class="navbar-nav me-auto">
                    @auth
                        @if(Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                   href="{{ route('admin.dashboard') }}">
                                    <i class="fa fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('admin.*') && !request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                   href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fa fa-cogs me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.users') }}">
                                            <i class="fa fa-users me-1"></i>Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.ph-color-charts') }}">
                                            <i class="fa fa-palette me-1"></i>pH Color Charts
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.npk-color-charts') }}">
                                            <i class="fa fa-seedling me-1"></i>NPK Color Charts
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                                   href="{{ route('dashboard') }}">
                                    <i class="fa fa-home me-1"></i>Dashboard
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('samples.*') ? 'active' : '' }}"
                               href="{{ route('samples.index') }}">
                                <i class="fa fa-flask me-1"></i>Soil Samples
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('farmers.*') ? 'active' : '' }}"
                               href="{{ route('farmers.index') }}">
                                <i class="fa fa-user-tie me-1"></i>Farmers
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('export*') ? 'active' : '' }}"
                               href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-file-csv me-1"></i>Export
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('export') }}">
                                        <i class="fa fa-file-csv me-1"></i>Full Export
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('export.phase2') }}">
                                        <i class="fa fa-microchip me-1"></i>Phase 2 Export
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>

                {{-- Right: user menu / guest links --}}
                <ul class="navbar-nav ms-auto align-items-center">
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa fa-user-circle me-1"></i>{{ Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">Login</a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Register</a>
                            </li>
                        @endif
                    @endauth
                </ul>

            </div>
        </div>
    </nav>

    {{-- ── Body: sidebar + main ────────────────────────────────── --}}
    <div class="app-body">

        @auth
        {{-- Sidebar --}}
        <nav class="sidebar d-none d-md-block">
            <ul class="nav flex-column">

                @if(Auth::user()->isAdmin())
                    <li><span class="sidebar-section">Admin</span></li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                           href="{{ route('admin.dashboard') }}">
                            <i class="fa fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}"
                           href="{{ route('admin.users') }}">
                            <i class="fa fa-users"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.ph-color-charts') ? 'active' : '' }}"
                           href="{{ route('admin.ph-color-charts') }}">
                            <i class="fa fa-palette"></i> pH Color Charts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.npk-color-charts') ? 'active' : '' }}"
                           href="{{ route('admin.npk-color-charts') }}">
                            <i class="fa fa-seedling"></i> NPK Color Charts
                        </a>
                    </li>
                    <li><div class="sidebar-divider"></div></li>
                @else
                    <li><span class="sidebar-section">Menu</span></li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">
                            <i class="fa fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li><div class="sidebar-divider"></div></li>
                @endif

                <li><span class="sidebar-section">Samples</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('samples.index') ? 'active' : '' }}"
                       href="{{ route('samples.index') }}">
                        <i class="fa fa-flask"></i> Soil Samples
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('samples.create') ? 'active' : '' }}"
                       href="{{ route('samples.create') }}">
                        <i class="fa fa-plus-circle"></i> New Sample
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('crops.requirements*') ? 'active' : '' }}"
                       href="{{ route('crops.requirements') }}">
                        <i class="fa fa-leaf"></i> Crop Requirements
                    </a>
                </li>

                <li><div class="sidebar-divider"></div></li>
                <li><span class="sidebar-section">Farmers</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('farmers.*') ? 'active' : '' }}"
                       href="{{ route('farmers.index') }}">
                        <i class="fa fa-user-tie"></i> Farmers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('farmers.import') ? 'active' : '' }}"
                       href="{{ route('farmers.import') }}">
                        <i class="fa fa-file-import"></i> Import Farmers
                    </a>
                </li>

                <li><div class="sidebar-divider"></div></li>
                <li><span class="sidebar-section">Export</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('export') && !request()->routeIs('export.phase2') ? 'active' : '' }}"
                       href="{{ route('export') }}">
                        <i class="fa fa-file-csv"></i> Full Export
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('export.phase2') ? 'active' : '' }}"
                       href="{{ route('export.phase2') }}">
                        <i class="fa fa-microchip"></i> Phase 2 Export
                    </a>
                </li>

                <li><div class="sidebar-divider"></div></li>
                <li><span class="sidebar-section">Support</span></li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('help.index') ? 'active' : '' }}"
                       href="{{ route('help.index') }}">
                        <i class="fa fa-circle-question"></i> Help &amp; Guidelines
                    </a>
                </li>

            </ul>
        </nav>
        @endauth

        {{-- Main content --}}
        <main class="main-content">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa fa-exclamation-circle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
