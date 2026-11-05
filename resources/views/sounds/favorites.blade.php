@extends('layouts.app')

@section('title', 'Suara Favorit — MiniSoundboard Gaming Edition')
@section('page_title', '⭐ Suara Favorit Saya')
@section('page_subtitle', 'Koleksi suara pilihan yang telah Anda tandai sebagai favorit untuk akses cepat.')

@section('content')
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

                    <!-- Unfavorite Button -->
                    <form method="POST" action="{{ route('sounds.favorite', $sound) }}" style="display: inline;">
                        @csrf
                        <button 
                            type="submit" 
                            class="btn btn-ghost btn-icon" 
                            style="width: 32px; height: 32px; font-size: 16px; color: #facc15;"
                            title="Hapus dari Favorit"
                        >
                            ★
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
        <span class="empty-state-icon">⭐</span>
        <h3 class="empty-state-title">Belum Ada Suara Favorit</h3>
        <p class="empty-state-desc">Anda belum menandai suara apapun sebagai favorit. Buka katalog suara dan klik tombol bintang untuk menambahkannya ke sini!</p>
        <a href="{{ route('sounds.catalog') }}" class="btn btn-primary">
            Jelajahi Katalog Suara
        </a>
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
