<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Dashboard')</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/responsive.css') }}" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 0px;
        }

        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1045;
            transition: all 0.3s ease;
        }

        .content-wrapper {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .sidebar.collapsed {
            margin-left: calc(var(--sidebar-width) * -1);
        }

        .content-wrapper.expanded {
            margin-left: 0;
        }

        @media (max-width: 991.98px) {
            .sidebar { margin-left: calc(var(--sidebar-width) * -1); }
            .content-wrapper { margin-left: 0; }
            .sidebar.show { margin-left: 0; }
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.75);
            border-radius: 8px;
            margin: 0 10px 5px 10px;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .nav-link.active { background: #0d6efd; }
        .main-content { flex: 1; }
    </style>
</head>

<body>

    <aside id="sidebar" class="sidebar bg-dark text-white d-flex flex-column">
        <div class="p-3 border-bottom border-secondary">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-white text-decoration-none">
                <div class="bg-primary p-0 rounded-3 me-1 d-flex align-items-center justify-content-center">
                    <img src="{{ asset('logo.png') }}" style="width: 40px; height: 40px;">
                </div>
                <span class="fs-4 fw-bold">PT GPU</span>
            </a>
        </div>

        <div class="flex-grow-1 overflow-auto pt-1">
            <ul class="nav nav-pills flex-column">

                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                        class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i>
                        Dashboard
                    </a>
                </li>

                {{-- MENU COLLAPSE --}}
                @role('developer')
                <li class="nav-item">

                    <a class="nav-link d-flex justify-content-between align-items-center"
                        data-bs-toggle="collapse"
                        href="#masterMenu"
                        role="button"
                        aria-expanded="{{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('dashboard.partners.*') ? 'true' : 'false' }}"
                        aria-controls="masterMenu">

                        <span>
                            <i class="bi bi-folder me-2"></i>
                            Master Data
                        </span>

                        <i class="bi bi-chevron-down"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('dashboard.permissions.*') ? 'show' : '' }}"
                        id="masterMenu">

                        <ul class="nav flex-column ms-3 mt-1">

                            @role('developer')
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}"
                                    class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                    <i class="bi bi-people me-2"></i>
                                    Users
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('roles.index') }}"
                                    class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                                    <i class="bi bi-shield-lock me-2"></i>
                                    Roles
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('permissions.index') }}"
                                    class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}">
                                    <i class="bi bi-key me-2"></i>
                                    Permissions
                                </a>
                            </li>
                            @endrole

                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="{{ route('dashboard.partners.index') }}"
                        class="nav-link {{ request()->routeIs('dashboard.partners.*') ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i>
                        Mitra Kerja
                    </a>
                </li>
                @endrole

                <li class="nav-item">
                    <a class="nav-link d-flex justify-content-between align-items-center"
                        data-bs-toggle="collapse"
                        href="#questionMenu"
                        role="button"
                        aria-expanded="{{ request()->routeIs('dashboard.question-categories.*') || request()->routeIs('dashboard.questions.*') ? 'true' : 'false' }}"
                        aria-controls="questionMenu">

                        <span>
                            <i class="bi bi-journal-text me-2"></i>
                            Bank Soal
                        </span>

                        <i class="bi bi-chevron-down"></i>
                    </a>

                    <div class="collapse {{ request()->routeIs('dashboard.question-categories.*') || request()->routeIs('dashboard.questions.*') ? 'show' : '' }}"
                        id="questionMenu">

                        <ul class="nav flex-column ms-3 mt-1">

                            <li class="nav-item">
                                <a href="{{ route('dashboard.question-categories.index') }}"
                                    class="nav-link {{ request()->routeIs('dashboard.question-categories.*') ? 'active' : '' }}">

                                    <i class="bi bi-folder2-open me-2"></i>
                                    Kategori Soal

                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('dashboard.questions.index') }}"
                                    class="nav-link {{ request()->routeIs('dashboard.questions.*') ? 'active' : '' }}">

                                    <i class="bi bi-patch-question me-2"></i>
                                    Daftar Soal

                                </a>
                            </li>

                        </ul>

                    </div>

                </li>

                <li class="nav-item">
                    <a href="{{ route('dashboard.manpowers.index') }}"
                        class="nav-link {{ request()->routeIs('dashboard.manpowers.*') ? 'active' : '' }}">
                        <i class="bi bi-person me-2"></i>
                        Manpower
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('dashboard.simper-categories.index') }}"
                        class="nav-link {{ request()->routeIs('dashboard.simper-categories.*') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill me-2"></i>
                        Kategori Simper
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('dashboard.simpers.pengajuan') }}"
                        class="nav-link 
                            {{ request()->routeIs('dashboard.simpers.pengajuan') ? 'active' : '' }}
                            {{ request()->routeIs('dashboard.simpers.create') ? 'active' : '' }}
                            {{ request()->routeIs('dashboard.simpers.edit') ? 'active' : '' }}
                            {{ request()->routeIs('dashboard.simpers.show') ? 'active' : '' }}
                         ">
                        <i class="bi bi-person-badge-fill me-2"></i>
                        Pengajuan Simper
                    </a>
                </li>

                <li class="nav-item">
                    <a href=""
                        class="nav-link ">
                        <i class="bi bi-person-badge-fill me-2"></i>
                        Hasil Ujian Simper
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('dashboard.simpers.index') }}"
                        class="nav-link {{ request()->routeIs('dashboard.simpers.index') ? 'active' : '' }}">
                        <i class="bi bi-person-badge-fill me-2"></i>
                        Simper
                    </a>
                </li>


                {{-- Sesi Ujian --}}
                <li class="nav-item">
                    <a href="{{ route('dashboard.exam-sessions.index') }}"
                        class="nav-link {{ request()->routeIs('dashboard.exam-sessions.index') ? 'active' : '' }}">
                        <i class="bi bi-journal-text me-2"></i>
                        Sesi Ujian
                    </a>
                </li>
                

            </ul>
        </div>

        <div class="p-3 border-top border-secondary bg-dark">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center">
                    <i class="bi bi-box-arrow-left me-2"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <div id="content" class="content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom sticky-top py-2 px-3">
            <div class="container-fluid p-0">
                <button class="btn btn-light border me-3" id="toggleBtn">
                    <i class="bi bi-list"></i>
                </button>

                <h5 class="mb-0 fw-semibold">@yield('title')</h5>

                <div class="ms-auto">
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle"
            data-bs-toggle="dropdown" aria-expanded="false">
            {{-- Dynamic Avatar based on Auth User Name --}}
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=0D6EFD&color=fff" 
                 width="32" height="32" class="rounded-circle me-2">
            
            {{-- Dynamic Auth User Name --}}
            <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
        </a>
        
        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
            <li class="px-3 py-2 d-md-none border-bottom mb-2">
                <span class="fw-bold d-block small text-muted">Signed in as</span>
                <span class="text-dark">{{ Auth::user()->name }}</span>
            </li>
            
            {{-- Profile Link --}}
            <li>
                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                    <i class="bi bi-person me-2 text-primary"></i> My Profile
                </a>
            </li>
            
            {{-- Activity Logs Link (Assumes you have this route defined) --}}
            <li>
                <a class="dropdown-item" href="{{ route('logs.self') }}">
                    <i class="bi bi-clock-history me-2 text-info"></i> Activity Logs
                </a>
            </li>
            
            <li><hr class="dropdown-divider"></li>
            
            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="dropdown-item text-danger border-0 bg-transparent">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>
            </div>
        </nav>

        <main class="main-content p-3 p-md-4 pt-lg-2">
            @yield('content')
        </main>

        <footer class="bg-white border-top py-3 mt-auto">
            <div class="container-fluid text-center">
                <p class="text-muted small mb-0">
                    Designed & Developed by <strong>Komang Chandra Winata</strong> © {{ date('Y') }} All Rights Reserved
                </p>
            </div>
        </footer>
    </div>

    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
    <script>
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');

        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth >= 992) {
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('show');
            }
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target) && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    </script>
</body>
</html>