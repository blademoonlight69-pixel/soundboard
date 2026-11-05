@extends('layouts.auth')

@section('title', 'Masuk — MiniSoundboard Gaming Edition')

@section('content')
<form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <div class="form-group">
        <label for="email" class="form-label">Email Gamer</label>
        <input 
            type="email" 
            id="email" 
            name="email" 
            value="{{ old('email') }}" 
            class="form-control @error('email') is-invalid @enderror" 
            placeholder="nama@contoh.com" 
            required 
            autofocus
        >
        @error('email')
            <div class="invalid-feedback">
                <span>⚠️</span>
                <span>{{ $message }}</span>
            </div>
        @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
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

    <div class="form-group d-flex align-center justify-between">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: var(--text-secondary);">
            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--accent-cyan);">
            Ingat saya
        </label>
    </div>

    <button type="submit" class="btn btn-primary w-full" style="margin-top: 8px;">
        <span>Masuk ke Arena</span>
        <span>⚡</span>
    </button>

    <div class="text-center" style="margin-top: 24px; font-size: 13px; color: var(--text-secondary);">
        Belum punya akun? 
        <a href="{{ route('register') }}" style="color: var(--accent-cyan); font-weight: 600;">Daftar Akun Baru</a>
    </div>
</form>
@endsection
