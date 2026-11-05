@extends('layouts.app')

@section('title', 'Admin Panel — MiniSoundboard Gaming Edition')
@section('page_title', '🛡️ Admin & Moderation Panel')
@section('page_subtitle', 'Pantau sistem, kelola pengguna, dan tangani moderasi suara yang dilaporkan.')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\User::count() }}</div>
        <div class="stat-label">Total Pengguna</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\Sound::count() }}</div>
        <div class="stat-label">Total Suara Terdaftar</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\Sound::where('status', 'hidden')->count() }}</div>
        <div class="stat-label">Suara Dimoderasi (Hidden)</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ \App\Models\SoundReport::where('status', 'pending')->count() }}</div>
        <div class="stat-label">Laporan Menunggu (Pending)</div>
    </div>
</div>

<div class="card card-neon mb-24">
    <div class="card-header">
        <h2 class="card-title">Prinsip Moderasi Suara</h2>
        <span class="badge badge-admin">PANDUAN ADMIN</span>
    </div>
    <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.7;">
        Sebagai Administrator, Anda berwenang menyembunyikan (<strong>hide</strong>) suara yang melanggar aturan komunitas atau hak cipta, serta menonaktifkan pengguna yang melanggar. Sesuai aturan arsitektur keamanan: <em>Admin tidak dapat mengubah isi judul, deskripsi, atau mengganti file suara milik pengguna lain.</em>
    </p>
</div>
@endsection
