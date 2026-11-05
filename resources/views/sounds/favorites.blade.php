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
        <button type="button" class="btn btn-danger btn-sm" onclick="stopAllAudio()" title="Hentikan Semua Audio (Stop All)">
            <span>⏹️</span>
            <span>Stop All</span>
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
    let currentAudio = null;
    let currentSoundId = null;
    let masterVolume = (document.getElementById('master-volume-slider')?.value || 80) / 100;

    function playAudio(soundId, audioUrl, title) {
        if (currentSoundId === soundId && currentAudio && !currentAudio.paused) {
            stopAllAudio();
            return;
        }

        stopAllAudio();

        const playerBar = document.getElementById('global-player-bar');
        const playerTitle = document.getElementById('player-title');
        const playerStatus = document.getElementById('player-status');
        const playBtn = document.getElementById('play-btn-' + soundId);
        const soundCard = document.getElementById('sound-card-' + soundId);

        currentAudio = new Audio(audioUrl);
        currentAudio.volume = masterVolume;
        currentSoundId = soundId;

        if (playBtn) playBtn.innerHTML = '⏸';
        if (soundCard) soundCard.classList.add('playing');
        if (playerBar) playerBar.style.display = 'flex';
        if (playerTitle) playerTitle.textContent = title;
        if (playerStatus) playerStatus.textContent = 'Sedang memutar...';

        currentAudio.play().catch(err => {
            console.error('Playback error:', err);
            if (playerStatus) playerStatus.textContent = 'Gagal memutar audio';
            stopAllAudio();
        });

        currentAudio.onended = () => {
            stopAllAudio();
            if (playerStatus) playerStatus.textContent = 'Selesai';
        };
    }

    function stopAllAudio() {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
            currentAudio = null;
        }

        if (currentSoundId) {
            const playBtn = document.getElementById('play-btn-' + currentSoundId);
            const soundCard = document.getElementById('sound-card-' + currentSoundId);
            if (playBtn) playBtn.innerHTML = '▶';
            if (soundCard) soundCard.classList.remove('playing');
            currentSoundId = null;
        }

        const playerStatus = document.getElementById('player-status');
        if (playerStatus) playerStatus.textContent = 'Dihentikan (Stop All)';
    }

    function changeMasterVolume(val) {
        masterVolume = val / 100;
        document.getElementById('volume-label').textContent = val + '%';
        if (currentAudio) {
            currentAudio.volume = masterVolume;
        }
    }
</script>
@endsection
