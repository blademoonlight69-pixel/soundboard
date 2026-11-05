@extends('layouts.app')

@section('title', 'Katalog Suara Publik — MiniSoundboard Gaming Edition')
@section('page_title', '🔊 Katalog Suara Publik')
@section('page_subtitle', 'Jelajahi dan dengarkan efek suara gaming populer yang dibagikan oleh komunitas.')

@section('content')
<!-- Filter & Pencarian Bar -->
<div class="card mb-24" style="padding: 18px 24px;">
    <form method="GET" action="{{ route('sounds.catalog') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 10px; flex: 1 1 240px; max-width: 100%;">
            <div class="search-bar" style="width: 100%;">
                <span class="search-icon">🔍</span>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Cari suara gaming, meme, sfx..."
                >
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Cari</button>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1 1 auto;">
            <select name="category" class="form-control" style="flex: 1 1 140px; min-width: 130px;" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->icon ?? '📁' }} {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <select name="sort" class="form-control" style="flex: 1 1 120px; min-width: 110px;" onchange="this.form.submit()">
                <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Terbaru</option>
                <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>Paling Populer</option>
                <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>Nama (A-Z)</option>
            </select>

            @if(request()->hasAny(['search', 'category', 'sort']))
                <a href="{{ route('sounds.catalog') }}" class="btn btn-ghost btn-sm">Reset</a>
            @endif

            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('upload-modal').style.display='flex'">
                <span>+ Upload Suara</span>
            </button>
        </div>
    </form>

    <!-- Category Pills Quick Filter -->
    <div style="display: flex; gap: 8px; margin-top: 14px; overflow-x: auto; padding-bottom: 4px;">
        <a 
            href="{{ route('sounds.catalog', request()->except('category', 'page')) }}" 
            class="badge {{ !request('category') ? 'badge-user' : 'badge-private' }}" 
            style="text-decoration: none; padding: 6px 12px; cursor: pointer;"
        >
            🌟 Semua
        </a>
        @foreach($categories as $cat)
            <a 
                href="{{ route('sounds.catalog', array_merge(request()->except('page'), ['category' => $cat->id])) }}" 
                class="badge {{ request('category') == $cat->id ? 'badge-user' : 'badge-private' }}" 
                style="text-decoration: none; padding: 6px 12px; cursor: pointer;"
            >
                {{ $cat->icon ?? '🎵' }} {{ $cat->name }}
            </a>
        @endforeach
    </div>
</div>

<!-- Sound Grid -->
@if($sounds->count() > 0)
    <div class="sounds-grid">
        @foreach($sounds as $sound)
            <div class="sound-card" id="sound-card-{{ $sound->id }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <button 
                        type="button" 
                        class="btn-play" 
                        id="play-btn-{{ $sound->id }}"
                        onclick="playAudio({{ $sound->id }}, '{{ route('sounds.play', $sound) }}', '{{ addslashes($sound->title) }}')"
                        title="Putar Suara"
                    >
                        ▶
                    </button>

                    <!-- Favorite Button -->
                    <form method="POST" action="{{ route('sounds.favorite', $sound) }}" style="display: inline;">
                        @csrf
                        @php
                            $isFav = $sound->favoritedBy->contains(auth()->id());
                        @endphp
                        <button 
                            type="submit" 
                            class="btn btn-ghost btn-icon" 
                            style="width: 32px; height: 32px; font-size: 16px; color: {{ $isFav ? '#facc15' : 'var(--text-muted)' }};"
                            title="{{ $isFav ? 'Hapus dari Favorit' : 'Tambah ke Favorit' }}"
                        >
                            {{ $isFav ? '★' : '☆' }}
                        </button>
                    </form>
                </div>

                <div class="sound-card-title" title="{{ $sound->title }}">{{ $sound->title }}</div>

                <div class="sound-card-meta">
                    @if($sound->category)
                        <span class="sound-card-category">{{ $sound->category->icon }} {{ $sound->category->name }}</span>
                    @endif
                    <span>▶ {{ number_format($sound->play_count) }}x</span>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; font-size: 11px; color: var(--text-muted);">
                    <span>Oleh: {{ $sound->user->name ?? 'Anonim' }}</span>
                    <span>{{ $sound->file_size_human }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 24px;">
        {{ $sounds->links() }}
    </div>
@else
    <div class="empty-state">
        <span class="empty-state-icon">🎧</span>
        <h3 class="empty-state-title">Belum Ada Suara yang Sesuai</h3>
        <p class="empty-state-desc">Tidak ditemukan suara dengan filter yang Anda gunakan. Jadilah yang pertama mengunggah suara di kategori ini!</p>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('upload-modal').style.display='flex'">
            + Upload Suara Baru
        </button>
    </div>
@endif

<!-- Fixed Player Bar di Bawah -->
<div class="player-bar" id="global-player-bar" style="display: none;">
    <div class="player-info">
        <div class="player-icon" id="player-icon">🎵</div>
        <div>
            <div class="player-name" id="player-title">Tidak ada audio aktif</div>
            <div class="player-status" id="player-status">Siap memutar</div>
        </div>
    </div>

    <div class="player-controls">
        <button type="button" class="btn btn-danger btn-sm" onclick="stopAllAudio()" title="Hentikan Semua Audio (Stop All [{{ auth()->user()->settings->stop_all_hotkey ?? 'End' }}])">
            <span>⏹️</span>
            <span>Stop All [{{ auth()->user()->settings->stop_all_hotkey ?? 'End' }}]</span>
        </button>
    </div>

    <div class="player-volume">
        <span style="font-size: 14px;">🔊</span>
        <input 
            type="range" 
            id="master-volume-slider" 
            class="volume-slider" 
            min="0" 
            max="100" 
            value="{{ auth()->user()->settings->master_volume ?? 80 }}" 
            oninput="changeMasterVolume(this.value)"
        >
        <span id="volume-label" style="font-size: 12px; min-width: 32px; color: var(--text-secondary);">
            {{ auth()->user()->settings->master_volume ?? 80 }}%
        </span>
    </div>
</div>

<!-- Modal Upload Suara Baru -->
<div id="upload-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Upload Efek Suara Gaming Baru</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('upload-modal').style.display='none'">✕</button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('sounds.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="title" class="form-label">Judul Suara *</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Contoh: Roblox Oof, Victory Horn..." required>
                </div>

                <div class="form-group">
                    <label for="category_id" class="form-label">Kategori</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">Pilih Kategori (Opsional)</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Visibilitas Akses *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="public">🌐 Publik (Dapat didengar & dicari oleh semua gamer)</option>
                        <option value="private">🔒 Privat (Hanya saya yang dapat melihat dan memutar)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Deskripsi Singkat</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Keterangan konteks suara ini..." rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label for="audio" class="form-label">File Audio (MP3, WAV, OGG, FLAC maks 10MB) *</label>
                    <input type="file" id="audio" name="audio" class="form-control" accept="audio/*" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('upload-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Unggah Suara 🚀</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.SOUNDBOARD_CONFIG = {
        stopAllHotkey: "{{ addslashes(auth()->user()->settings->stop_all_hotkey ?? 'End') }}",
        masterVolume: {{ auth()->user()->settings->master_volume ?? 80 }},
        hotkeys: {
            @foreach(auth()->user()->hotkeys()->with('sound')->get() as $hk)
                @if($hk->sound)
                    "{{ strtoupper(trim($hk->key_combo)) }}": {
                        id: {{ $hk->sound->id }},
                        url: "{{ route('sounds.play', $hk->sound) }}",
                        title: "{{ addslashes($hk->sound->title) }}"
                    },
                @endif
            @endforeach
        }
    };
</script>
<script src="{{ asset('js/soundboard-player.js') }}"></script>
@endsection
