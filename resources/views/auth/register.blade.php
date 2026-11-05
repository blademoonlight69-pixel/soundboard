@extends('layouts.auth')

@section('title', 'Daftar Akun — MiniSoundboard Gaming Edition')

@section('content')
<form method="POST" action="{{ route('register.submit') }}">
    @csrf

    <div class="form-group">
        <label for="name" class="form-label">Username / Nickname</label>
        <input 
            type="text" 
            id="name" 
            name="name" 
            value="{{ old('name') }}" 
            class="form-control @error('name') is-invalid @enderror" 
            placeholder="GamerTag99" 
            required 
            autofocus
        >
        @error('name')
            <div class="invalid-feedback">
                <span>⚠️</span>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="form-group">
        <label for="email" class="form-label">Alamat Email</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            class="form-control @error('email') is-invalid @enderror" 
            placeholder="nama@contoh.com" 
            required
        >
        @error('email')
            <div class="invalid-feedback">
                <span>⚠️</span>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password (Min. 8 Karakter)</label>
        <input 
            type="password" 
            id="password" 
            name="password" 
            class="form-control @error('password') is-invalid @enderror" 
            placeholder="••••••••" 
            required
        >
        @error('password')
            <div class="invalid-feedback">
                <span>⚠️</span>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <input 
            type="password" 
            id="password_confirmation" 
            name="password_confirmation" 
            class="form-control" 
            placeholder="••••••••" 
            required
        >
    </div>

    <button type="submit" class="btn btn-primary w-full" style="margin-top: 8px;">
        <span>Buat Akun Gaming</span>
        <span>🚀</span>
    </button>

    <div class="text-center" style="margin-top: 24px; font-size: 13px; color: var(--text-secondary);">
        Sudah punya akun? 
        <a href="{{ route('login') }}" style="color: var(--accent-cyan); font-weight: 600;">Masuk di Sini</a>
    </div>
</form>
@endsection
