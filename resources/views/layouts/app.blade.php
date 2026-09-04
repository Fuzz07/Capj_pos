<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CAPTAiN J POS System')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/capj.jpg') }}">

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Keep horizontal bleed clipped at the viewport so the page itself stays
           the vertical scroll container (inner scrolling breaks keyboard paging,
           anchor links and window.scrollTo). */
        html {
            overflow-x: hidden;
        }

        /* Height of the sticky top bar — sticky page elements offset from it */
        :root {
            --topbar-h: 52px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7f2;
            color: #2f3b2f;
            display: flex;
            min-height: 100vh;
            margin: 0;
        }

        .sidebar {
            width: 230px;
            background: linear-gradient(180deg, #f10000 0%, #b30000 100%);
            color: #fff;
            padding-top: 1.25rem;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand {
            padding: 0 1rem 1rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 0.75rem;
        }

        .sidebar-brand img {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            object-fit: cover;
            margin-bottom: 6px;
        }

        .sidebar-brand h6 {
            margin: 0;
            font-weight: 800;
            font-size: 0.92rem;
            letter-spacing: 0.5px;
            color: #fff;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            padding: 0 0.75rem;
        }

        .sidebar-nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            font-size: 0.83rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-nav a i {
            font-size: 0.95rem;
            width: 20px;
            text-align: center;
            transition: transform 0.2s ease;
        }

        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background-color: #ffffff;
            color: #d32f2f !important;
            font-weight: 700;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 8px;
        }

        .sidebar-nav a:hover i,
        .sidebar-nav a.active i {
            transform: scale(1.15);
            color: #d32f2f;
        }

        .sidebar-footer {
            padding: 0.6rem 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.4rem 0.55rem;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            flex: 1;
            min-width: 0;
        }

        .user-badge-icon {
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: #fff;
            flex-shrink: 0;
        }

        .user-badge-info {
            overflow: hidden;
            text-align: left;
            min-width: 0;
        }

        .user-badge-label {
            font-size: 0.58rem;
            color: rgba(255, 255, 255, 0.75);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .user-badge-name {
            font-size: 0.78rem;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }

        .sidebar-footer .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.4rem 0.65rem;
            background: rgba(211, 47, 47, 0.25);
            border: 1px solid rgba(211, 47, 47, 0.45);
            border-radius: 8px;
            color: #ffb3b3;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-footer .btn-logout:hover {
            background: rgba(211, 47, 47, 0.55);
            color: #fff;
        }

        /* Top bar: sits above page content, holds the account actions on the right */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1030;
            margin: -2rem -2rem 1.25rem -2rem;
            padding: 0.55rem 1.25rem;
            background: #ffffff;
            border-bottom: 1px solid #e9edf2;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            min-height: var(--topbar-h);
        }

        .topbar-user {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            line-height: 1.1;
            min-width: 0;
        }

        .topbar-user-label {
            font-size: 0.58rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
        }

        .topbar-user-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        .btn-logout-top {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.45rem 0.95rem;
            background: linear-gradient(135deg, #f10000 0%, #b30000 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.78rem;
            border-radius: 8px;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 8px -2px rgba(220, 38, 38, 0.35);
            transition: all 0.2s ease-in-out;
            white-space: nowrap;
        }

        .btn-logout-top:hover {
            background: linear-gradient(135deg, #b30000 0%, #7f0000 100%);
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(220, 38, 38, 0.45);
        }

        .main-content {
            margin-left: 230px;
            padding: 2rem;
            width: calc(100% - 230px);
            min-width: 0;
        }

        /* Mobile specific hamburger button (hidden on desktop) */
        .hamburger {
            display: none;
        }

        .sidebar-overlay {
            display: none;
        }

        /* Mobile & portrait tablets: off-canvas sidebar with hamburger */
        @media (max-width: 991.98px) {
            body {
                display: block;
                height: auto;
                min-height: 100vh;
            }

            .sidebar {
                left: -250px;
                width: 240px;
                transition: left 0.3s;
            }

            .sidebar.show {
                left: 0;
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.35);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 4rem 1rem 1.5rem 1rem;
            }

            .topbar {
                margin: -4rem -1rem 1rem -1rem;
                padding-left: 62px;
            }

            .hamburger {
                display: flex;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 10px;
                left: 10px;
                z-index: 1100;
                background: #f10000;
                color: white;
                border: none;
                width: 42px;
                height: 42px;
                font-size: 1.15rem;
                border-radius: 8px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
        }

        /* Small phones: tighter paddings, stack page headers */
        @media (max-width: 575.98px) {
            .main-content {
                padding: 3.75rem 0.6rem 1.25rem 0.6rem;
            }

            .topbar {
                margin: -3.75rem -0.6rem 1rem -0.6rem;
                padding-left: 60px;
                padding-right: 0.7rem;
            }

            .topbar-user {
                display: none;
            }

            .main-content .container-fluid.px-4 {
                padding-left: 0.35rem !important;
                padding-right: 0.35rem !important;
            }

            .main-content h3 {
                font-size: 1.25rem;
            }
        }

        /* Pagination
           Bootstrap's paginator ships two blocks: a compact prev/next for phones
           and a full numbered one for larger screens. Only style them — never
           force `display`, or both render at once and stack awkwardly. */
        .pagination {
            justify-content: center;
            margin: 0;
            flex-wrap: wrap;
            gap: 0.3rem;
        }

        .page-link {
            border: 1px solid #e9edf2;
            border-radius: 0.5rem;
            color: #475569;
            font-weight: 600;
            font-size: 0.83rem;
            line-height: 1.3;
            min-width: 38px;
            padding: 0.45rem 0.7rem;
            text-align: center;
            transition: all 0.15s ease-in-out;
        }

        .page-link:hover {
            background-color: #fff1f1;
            border-color: #f3b8b8;
            color: #b30000;
        }

        .page-link:focus {
            box-shadow: 0 0 0 3px rgba(241, 0, 0, 0.15);
            color: #b30000;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #ff1e1e 0%, #b30000 100%);
            border-color: #b30000;
            color: #ffffff;
            box-shadow: 0 4px 10px -3px rgba(241, 0, 0, 0.45);
        }

        .page-item.disabled .page-link {
            background-color: #f8fafc;
            border-color: #eef2f7;
            color: #cbd5e1;
        }

        /* "Showing 1 to 15 of 151 results" line */
        nav[role="navigation"] p.small,
        nav[role="navigation"] .text-muted {
            margin-bottom: 0;
            font-size: 0.8rem;
            color: #64748b;
        }

        @media (max-width: 575.98px) {
            .page-link {
                min-width: 34px;
                padding: 0.4rem 0.55rem;
                font-size: 0.78rem;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    @auth
        <button class="hamburger" id="hamburgerBtn">&#9776;</button>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div>
                <div class="sidebar-brand">
                    <img src="{{ asset('images/capj.jpg') }}" alt="CAPTAiN J"
                        onerror="this.src='https://ui-avatars.com/api/?name=CAPTAiN+J&background=random';">
                    <h6>CAPTAiN J {{ ucfirst(auth()->user()->role) }}</h6>
                </div>

                <nav class="sidebar-nav">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-id-card"></i> <span>Profile</span>
                        </a>
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-line"></i> <span>Sales &amp; Reports</span>
                        </a>
                        <a href="{{ route('inventory.index') }}"
                            class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-boxes-stacked"></i> <span>Inventory</span>
                        </a>
                        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i> <span>Users</span>
                        </a>
                        <a href="{{ route('pos.index') }}" class="{{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-cash-register"></i> <span>Create Order</span>
                        </a>
                        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-receipt"></i> <span>Orders History</span>
                        </a>
                        <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-list-check"></i> <span>Activity Logs</span>
                        </a>
                        <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-gear"></i> <span>Settings</span>
                        </a>
                    @elseif(auth()->user()->role === 'staff')
                        <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-id-card"></i> <span>Profile</span>
                        </a>
                        <a href="{{ route('inventory.index') }}"
                            class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-boxes-stacked"></i> <span>Inventory</span>
                        </a>
                        <a href="{{ route('pos.index') }}" class="{{ request()->routeIs('pos.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-cash-register"></i> <span>Create Order</span>
                        </a>
                        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-receipt"></i> <span>Orders History</span>
                        </a>
                    @endif
                </nav>
            </div>
        </div>
    @endauth

        <!-- Main Content -->
        <div class="main-content" @guest style="margin-left: 0; width: 100%;" @endguest>
            @auth
                <!-- Top Bar -->
                <header class="topbar">
                    <div class="topbar-user">
                        <span class="topbar-user-label">Logged in as</span>
                        <span class="topbar-user-name">{{ auth()->user()->full_name ?? auth()->user()->username }}</span>
                    </div>
                    <a href="/logout" class="btn-logout-top">
                        <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
                    </a>
                </header>
            @endauth

            <!-- Global Alerts Container -->
            <div class="container-fluid px-4 mt-3">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>

            @yield('content')
        </div>

        <!-- Bootstrap 5 Bundle JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const btn = document.getElementById('hamburgerBtn');
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');

                if (btn && sidebar && overlay) {
                    btn.addEventListener('click', function () {
                        sidebar.classList.toggle('show');
                        overlay.classList.toggle('show');
                    });
                    overlay.addEventListener('click', function () {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    });
                }
            });

            // Intercept any delete confirmation forms
            document.addEventListener('submit', function (e) {
                const form = e.target;
                if (form && form.classList.contains('confirm-delete')) {
                    e.preventDefault(); // Prevent direct submission

                    const message = form.getAttribute('data-confirm-message') || 'Are you sure you want to delete this?';

                    Swal.fire({
                        title: 'Are you sure?',
                        text: message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545', // Brand styled red
                        cancelButtonColor: '#6c757d', // Secondary gray
                        confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Yes, Delete',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        background: '#ffffff',
                        customClass: {
                            popup: 'rounded-4 shadow',
                            confirmButton: 'btn btn-danger px-4 fw-semibold',
                            cancelButton: 'btn btn-light border px-4 fw-semibold me-2'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }
            });
        </script>

        @auth
        {{-- ================================================================
             SECURITY: Auto-logout when the tab/window is closed.
             Single-session-per-user enforcement is handled server-side by
             App\Http\Middleware\SingleSessionMiddleware — no client JS needed
             for that. This block only handles the tab/window close case.
        --}}
        <script>
        (function () {
            'use strict';

            var navigating = false;

            // Mark intentional same-origin navigations so beforeunload
            // does NOT fire the beacon (we're just changing pages, not closing).
            document.addEventListener('click', function (e) {
                var a = e.target.closest('a[href]');
                if (a) {
                    try {
                        if (new URL(a.href, location.origin).origin === location.origin) {
                            navigating = true;
                        }
                    } catch (_) {}
                }
            }, true);
            document.addEventListener('submit', function () { navigating = true; }, true);

            // When the tab/window is truly closed, silently invalidate the session
            // on the server so the next person who opens the browser must log in.
            window.addEventListener('beforeunload', function () {
                if (navigating) return;

                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (!csrfMeta) return;

                var fd = new FormData();
                fd.append('_token', csrfMeta.getAttribute('content'));
                navigator.sendBeacon('/logout-beacon', fd);
            });
        })();
        </script>
        @endauth

        @stack('scripts')
</body>

</html>