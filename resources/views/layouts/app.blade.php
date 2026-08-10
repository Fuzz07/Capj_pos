<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CAPTAiN J POS System')</title>

    <!-- Google Fonts & FontAwesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7f2;
            color: #2f3b2f;
            display: flex;
            height: 100vh;
            margin: 0;
            overflow-x: hidden;
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
            padding: 1rem 0.75rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(0, 0, 0, 0.12);
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.45rem 0.6rem;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            margin-bottom: 0.6rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
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

        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.5rem 0.75rem;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
            color: #dc2626 !important;
            font-weight: 700;
            font-size: 0.8rem;
            border-radius: 8px;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: #ffffff !important;
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(220, 38, 38, 0.4);
        }

        .main-content {
            margin-left: 230px;
            padding: 2rem;
            width: calc(100% - 230px);
            overflow-y: auto;
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
                overflow-y: visible;
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
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
        }

        /* Small phones: tighter paddings, stack page headers */
        @media (max-width: 575.98px) {
            .main-content {
                padding: 3.75rem 0.6rem 1.25rem 0.6rem;
            }
            .main-content .container-fluid.px-4 {
                padding-left: 0.35rem !important;
                padding-right: 0.35rem !important;
            }
            .main-content h3 {
                font-size: 1.25rem;
            }
        }

        /* Centered Modern Pagination Override */
        nav.d-flex.justify-content-between,
        div.d-none.flex-sm-fill.d-sm-flex.align-items-sm-center.justify-content-sm-between,
        div.d-sm-flex.align-items-sm-center.justify-content-sm-between {
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 0.5rem !important;
            text-align: center !important;
            width: 100% !important;
            display: flex !important;
        }
        .pagination {
            justify-content: center !important;
            margin: 0.25rem 0 0 0 !important;
            flex-wrap: wrap;
            row-gap: 0.3rem;
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
                <img src="{{ asset('images/capj.jpg') }}" alt="CAPTAiN J" onerror="this.src='https://ui-avatars.com/api/?name=CAPTAiN+J&background=random';">
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
                    <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-boxes-stacked"></i> <span>Inventory</span>
                    </a>
                    <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-users"></i> <span>Users</span>
                    </a>
                    <a href="{{ route('pos.index') }}" class="{{ request()->routeIs('pos.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cash-register"></i> <span>Create Order</span>
                    </a>
                    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> <span>Orders</span>
                    </a>
                    <a href="{{ route('logs.index') }}" class="{{ request()->routeIs('logs.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-list-check"></i> <span>Activity Logs</span>
                    </a>
                @elseif(auth()->user()->role === 'staff')
                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-id-card"></i> <span>Profile</span>
                    </a>
                    <a href="{{ route('inventory.index') }}" class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-boxes-stacked"></i> <span>Inventory</span>
                    </a>
                    <a href="{{ route('pos.index') }}" class="{{ request()->routeIs('pos.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-cash-register"></i> <span>Create Order</span>
                    </a>
                    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-receipt"></i> <span>Orders</span>
                    </a>
                @endif
            </nav>
        </div>

        <div class="sidebar-footer">
            <div class="user-badge">
                <div class="user-badge-icon"><i class="fa-solid fa-circle-user"></i></div>
                <div class="user-badge-info">
                    <div class="user-badge-label">Logged in as</div>
                    <div class="user-badge-name">{{ auth()->user()->full_name ?? auth()->user()->username }}</div>
                </div>
            </div>
            <a href="/logout" class="btn-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
    @endauth

    <!-- Main Content -->
    <div class="main-content" @guest style="margin-left: 0; width: 100%;" @endguest>
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
        document.addEventListener("DOMContentLoaded", function() {
            const btn = document.getElementById('hamburgerBtn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if(btn && sidebar && overlay) {
                btn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
                overlay.addEventListener('click', function() {
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
    @stack('scripts')
</body>
</html>
