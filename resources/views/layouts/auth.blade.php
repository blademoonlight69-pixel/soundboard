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
    <meta name="theme-color" content="#060611">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
</head>
<body>
    <div class="auth-layout">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-brand-icon">🎮</div>
                <h1 class="auth-title">MiniSoundboard</h1>
                <p class="auth-subtitle">Gaming Edition & Audio SFX</p>
            </div>

            {{-- Flash Messages --}}
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

            @yield('content')
        </div>
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
