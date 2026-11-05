@extends('layouts.app')

@section('title', 'Dashboard — MiniSoundboard Gaming Edition')
@section('page_title', 'Gaming Soundboard Arena')
@section('page_subtitle', 'Pusat kendali sound efek, hotkey instan, dan koleksi audio kamu.')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">{{ auth()->user()->sounds()->count() }}</div>
        <div class="stat-label">Suara Saya</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ auth()->user()->favorites()->count() }}</div>
        <div class="stat-label">Favorit</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\Sound::where('status', 'public')->count() }}</div>
        <div class="stat-label">Katalog Publik</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\Category::count() }}</div>
        <div class="stat-label">Kategori Audio</div>
    </div>
</div>

<div class="card card-neon mb-24">
    <div class="card-header">
        <h2 class="card-title">Selamat Datang, {{ auth()->user()->name }}! 🎧</h2>
        <span class="badge {{ auth()->user()->isAdmin() ? 'badge-admin' : 'badge-user' }}">
            {{ auth()->user()->isAdmin() ? 'ADMINISTRATOR' : 'GAMER' }}
        </span>
    </div>
    <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 14px;">
        Pusat kendali efek suara gaming Anda sudah aktif! Anda dapat langsung menjelajahi katalog suara publik, mengunggah audio pribadi, mengatur hotkey instan, dan memutar suara saat streaming atau bermain game.
    </p>

    <!-- Quick Action Navigation -->
    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
        <a href="{{ route('sounds.catalog') }}" class="btn btn-primary btn-sm">
            <span>🔊 Buka Katalog Publik</span>
        </a>
        <a href="{{ route('my-sounds') }}" class="btn btn-secondary btn-sm">
            <span>📁 Koleksi Saya ({{ auth()->user()->sounds()->count() }})</span>
        </a>
        <a href="{{ route('favorites') }}" class="btn btn-ghost btn-sm" style="border-color: rgba(250, 204, 21, 0.3); color: #facc15;">
            <span>⭐ Favorit ({{ auth()->user()->favorites()->count() }})</span>
        </a>
        <a href="{{ route('hotkeys') }}" class="btn btn-ghost btn-sm" style="border-color: var(--border-accent); color: var(--accent-cyan);">
            <span>⌨️ Hotkey Manager ({{ auth()->user()->hotkeys()->count() }})</span>
        </a>
    </div>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <div style="background: var(--bg-glass); border: 1px solid var(--border); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; color: var(--accent-cyan);">
            ✓ Sesi aktif sebagai: <strong>{{ auth()->user()->email }}</strong>
        </div>
        <div style="background: var(--bg-glass); border: 1px solid var(--border); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; color: var(--accent-purple);">
            ✓ Master Volume Default: <strong>{{ auth()->user()->settings->master_volume ?? 80 }}%</strong>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Kategori SFX Tersedia ({{ \App\Models\Category::count() }})</h3>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
        @foreach(\App\Models\Category::orderBy('sort_order')->get() as $cat)
            <div style="background: var(--bg-glass); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 14px; text-align: center; transition: all 0.2s;">
                <div style="font-size: 24px; margin-bottom: 6px;">{{ $cat->icon ?? '🎵' }}</div>
                <div style="font-size: 13px; font-weight: 600; color: white;">{{ $cat->name }}</div>
                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">{{ $cat->sounds()->count() }} Suara</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
