<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'MiniSoundboard — Gaming Edition')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Orbitron:wght@600;800;900&family=Rajdhani:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- CSS Design System -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#060611">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
</head>
<body>
    <div class="app-layout">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <a href="{{ route('dashboard') }}" class="brand-logo">
                        <div class="brand-icon">🎮</div>
                        <div>
                            <div class="brand-name">MiniSoundboard</div>
                            <div class="brand-tagline">Gaming Edition</div>
                        </div>
                    </a>
                    <button type="button" class="sidebar-close-btn" onclick="document.getElementById('sidebar').classList.remove('open'); document.getElementById('sidebar-overlay').classList.remove('active');" aria-label="Tutup Menu">✕</button>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section-title">Menu Utama</div>
                
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="nav-icon">⚡</span>
                    <span>Dashboard</span>
                </a>

                {{-- Placeholder navigation links (will be activated in subsequent steps) --}}
                <a href="{{ url('/sounds/catalog') }}" class="nav-link {{ request()->is('sounds/catalog*') ? 'active' : '' }}">
                    <span class="nav-icon">🔊</span>
                    <span>Katalog Publik</span>
                </a>

                <a href="{{ url('/my-sounds') }}" class="nav-link {{ request()->is('my-sounds*') ? 'active' : '' }}">
                    <span class="nav-icon">📁</span>
                    <span>Koleksi Saya</span>
                </a>

                <a href="{{ url('/favorites') }}" class="nav-link {{ request()->is('favorites*') ? 'active' : '' }}">
                    <span class="nav-icon">⭐</span>
                    <span>Favorit</span>
                </a>

                <a href="{{ url('/hotkeys') }}" class="nav-link {{ request()->is('hotkeys*') ? 'active' : '' }}">
                    <span class="nav-icon">⌨️</span>
                    <span>Hotkey Manager</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="nav-section-title" style="margin-top: 24px; color: var(--accent-purple);">Admin Panel</div>
                    
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" style="color: var(--accent-purple);">
                        <span class="nav-icon">🛡️</span>
                        <span>Moderasi & Admin</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" style="color: var(--accent-purple);">
                        <span class="nav-icon">👥</span>
                        <span>Pengguna & Role</span>
                    </a>
                @endif
            </nav>

            <!-- User Info & Logout -->
            <div class="sidebar-user" style="padding-bottom: 90px;">
                <div class="user-card">
                    <div class="user-avatar" style="{{ auth()->user()->isSuperAdmin() ? 'background: linear-gradient(135deg, #eab308, #f59e0b);' : '' }}">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="user-info">
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <div class="user-role {{ auth()->user()->isAdmin() ? 'admin' : '' }}" style="{{ auth()->user()->isSuperAdmin() ? 'color: #facc15;' : '' }}">
                            @if(auth()->user()->isSuperAdmin())
                                👑 Super Admin
                            @elseif(auth()->user()->isExactAdmin())
                                🛡️ Admin
                            @else
                                🎮 Gamer
                            @endif
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" style="margin-top: 14px;">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm w-full" style="justify-content: center; gap: 8px; font-weight: 600; border: 1px solid rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.12);">
                        <span>🚪</span>
                        <span>Keluar / Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Overlay untuk Mobile Sidebar -->
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open'); this.classList.remove('active');"></div>

        <!-- MAIN CONTENT AREA -->
        <main class="main-content">
            <!-- Topbar (Tersedia di semua viewport: memuat navigasi hamburger mobile, profil ringkas & Logout) -->
            <header class="topbar-desktop">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button type="button" class="hamburger" id="hamburger-toggle" onclick="document.getElementById('sidebar').classList.toggle('open'); document.getElementById('sidebar-overlay').classList.toggle('active');" aria-label="Buka Navigasi Mobile">☰</button>
                    <span style="font-family: var(--font-display); font-size: 13px; letter-spacing: 0.1em; color: var(--text-muted); text-transform: uppercase;" class="topbar-title-desktop">MiniSoundboard Gaming Arena</span>
                </div>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary);">
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent-green); box-shadow: 0 0 8px var(--accent-green); display: inline-block;"></span>
                        <strong style="color: white;" class="topbar-username">{{ auth()->user()->name }}</strong>
                        <span class="badge {{ auth()->user()->isSuperAdmin() ? 'badge-admin' : (auth()->user()->isExactAdmin() ? 'badge-admin' : 'badge-user') }} badge-sm">
                            {{ auth()->user()->isSuperAdmin() ? 'SUPER ADMIN' : (auth()->user()->isExactAdmin() ? 'ADMIN' : 'GAMER') }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm" style="border: 1px solid rgba(239, 68, 68, 0.35); color: #f87171; background: rgba(239, 68, 68, 0.08); padding: 5px 12px; font-size: 12px; gap: 6px;">
                            <span>🚪</span>
                            <span class="btn-logout-text">Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-header">
                @if (session('success'))
                    <div class="alert alert-success">
                        <span>✓</span>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        <span>⚠️</span>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <h1 class="page-title">@yield('page_title', 'Dashboard')</h1>
                <p class="page-subtitle">@yield('page_subtitle', 'Selamat datang di MiniSoundboard')</p>
            </div>

            <div class="page-content">
                @yield('content')
            </div>
        </main>
    </div>
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ asset('sw.js') }}').then(function(reg) {
                    console.log('[PWA] Service Worker aktif:', reg.scope);
                }).catch(function(err) {
                    console.warn('[PWA] Service Worker gagal dimuat:', err);
                });
            });
        }
    </script>
</body>
</html>
