@extends('layouts.app')

@section('title', 'Manajemen Pengguna — Admin Panel')
@section('page_title', '👥 Manajemen Pengguna & Administrator')
@section('page_subtitle', 'Pantau akun gamer, atur status keaktifan, dan kelola role pengguna.')

@section('content')
@if (session('info'))
    <div class="alert alert-info">
        <span>ℹ️</span>
        <span>{{ session('info') }}</span>
    </div>
@endif

<div class="card mb-24">
    <div class="card-header">
        <h2 class="card-title">Daftar Akun Pengguna ({{ $users->total() }})</h2>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span class="badge {{ auth()->user()->isSuperAdmin() ? 'badge-admin' : 'badge-user' }}">
                Anda: {{ strtoupper(auth()->user()->role) }}
            </span>
        </div>
    </div>

    <!-- Filter & Search -->
    <form method="GET" action="{{ route('admin.users.index') }}" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <input 
            type="text" 
            name="search" 
            value="{{ request('search') }}" 
            placeholder="Cari nama atau email..." 
            class="form-control" 
            style="flex: 1 1 200px; max-width: 100%;"
        >
        <select name="role" class="form-control" style="flex: 1 1 140px; max-width: 100%;">
            <option value="">Semua Role</option>
            <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        @if(request()->hasAny(['search', 'role']))
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Reset</a>
        @endif
    </form>

    <!-- Table -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Bergabung</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 13px;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: white;">{{ $user->name }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($user->isSuperAdmin())
                                <span class="badge badge-admin">
                                    👑 SUPER ADMIN
                                </span>
                            @elseif($user->isExactAdmin())
                                <span class="badge badge-admin">
                                    🛡️ ADMIN
                                </span>
                            @else
                                <span class="badge badge-user">
                                    🎮 USER
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_active)
                                <span style="color: var(--accent-green); font-size: 13px; font-weight: 500;">● Aktif</span>
                            @else
                                <span style="color: var(--accent-red); font-size: 13px; font-weight: 500;">● Nonaktif</span>
                            @endif
                        </td>
                        <td style="font-size: 12px; color: var(--text-muted);">
                            {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 8px; align-items: center;">
                                <!-- Toggle Active / Nonaktif -->
                                @if(!$user->isLastActiveSuperAdmin() && (auth()->user()->isSuperAdmin() || (!$user->isAdmin())))
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" style="display: inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button 
                                            type="submit" 
                                            class="btn btn-sm {{ $user->is_active ? 'btn-ghost' : 'btn-success' }}"
                                            style="padding: 4px 10px; font-size: 12px;"
                                            onclick="return confirm('{{ $user->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan kembali akun ini?' }}')"
                                        >
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endif

                                <!-- Ubah Role (Hanya untuk Super Admin) -->
                                @if(auth()->user()->isSuperAdmin())
                                    <form method="POST" action="{{ route('admin.users.role', $user) }}" style="display: inline-flex; gap: 6px; align-items: center;">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="form-control" style="padding: 4px 8px; font-size: 12px; height: 30px; width: 110px;">
                                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                            <option value="super_admin" {{ $user->role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                                        </select>
                                        <button 
                                            type="submit" 
                                            class="btn btn-primary btn-sm" 
                                            style="padding: 4px 8px; font-size: 12px;"
                                            {{ $user->isLastActiveSuperAdmin() ? 'disabled title="Super Admin terakhir tidak dapat diubah"' : '' }}
                                        >
                                            Ubah
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 30px; color: var(--text-muted);">
                            Tidak ada pengguna yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 16px;">
        {{ $users->links() }}
    </div>
</div>
@endsection
