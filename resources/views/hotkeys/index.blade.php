@extends('layouts.app')

@section('title', 'Hotkey Manager — MiniSoundboard Gaming Edition')
@section('page_title', '⌨️ Hotkey Manager')
@section('page_subtitle', 'Petakan tombol keyboard ke efek suara favorit Anda untuk pemutaran instan saat streaming atau gaming.')

@section('content')
<!-- Active Listener Banner -->
<div class="card card-neon mb-24" style="background: rgba(0, 245, 255, 0.05); border-color: rgba(0, 245, 255, 0.3);">
    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="font-size: 28px; animation: pulse-glow 2s infinite;">⚡</div>
            <div>
                <h3 style="font-size: 15px; font-weight: 700; color: white; margin-bottom: 2px;">
                    In-Browser Hotkey Listener: <span style="color: var(--accent-green);">AKTIF</span>
                </h3>
                <p style="font-size: 13px; color: var(--text-secondary);">
                    Tekan tombol yang telah dipetakan pada keyboard Anda kapan saja selama tab browser ini aktif untuk memutar suara!
                </p>
            </div>
        </div>

        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('add-hotkey-modal').style.display='flex'">
            + Tambah Hotkey Baru
        </button>
    </div>
</div>

<!-- Hotkey List -->
<div class="card mb-24">
    <div class="card-header">
        <h3 class="card-title">Daftar Hotkey Terdaftar ({{ $hotkeys->count() }})</h3>
        <span class="badge badge-user">Browser Active Hotkeys</span>
    </div>

    @if($hotkeys->count() > 0)
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Tombol Key</th>
                        <th>Suara yang Dipetakan</th>
                        <th>Kategori</th>
                        <th>Label Tampilan</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hotkeys as $hk)
                        <tr id="hotkey-row-{{ $hk->id }}">
                            <td>
                                <kbd style="background: rgba(0, 245, 255, 0.15); border: 1px solid var(--accent-cyan); color: var(--accent-cyan); padding: 5px 12px; border-radius: var(--radius-sm); font-family: var(--font-display); font-size: 13px; font-weight: 700; display: inline-block;">
                                    {{ $hk->key_combo }}
                                </kbd>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: white;">{{ $hk->sound->title ?? 'Suara Dihapus' }}</div>
                            </td>
                            <td>
                                @if($hk->sound && $hk->sound->category)
                                    <span class="sound-card-category">{{ $hk->sound->category->icon }} {{ $hk->sound->category->name }}</span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td style="color: var(--text-secondary); font-size: 13px;">
                                {{ $hk->display_label }}
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 8px;">
                                    @if($hk->sound)
                                        <button 
                                            type="button" 
                                            class="btn btn-ghost btn-sm" 
                                            style="padding: 4px 10px; font-size: 12px;"
                                            onclick="playAudio({{ $hk->sound->id }}, '{{ route('sounds.play', $hk->sound) }}', '{{ addslashes($hk->sound->title) }}')"
                                        >
                                            ▶ Test Play
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('hotkeys.destroy', $hk) }}" style="display: inline;" onsubmit="return confirm('Hapus pemetaan hotkey [{{ $hk->key_combo }}]?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 10px; font-size: 12px;">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty-state">
            <span class="empty-state-icon">⌨️</span>
            <h3 class="empty-state-title">Belum Ada Hotkey Dikonfigurasi</h3>
            <p class="empty-state-desc">Petakan tombol keyboard Anda (seperti 1, 2, Q, E, atau F1) ke suara gaming untuk memudahkan pemutaran instan.</p>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('add-hotkey-modal').style.display='flex'">
                + Buat Pemetaan Hotkey Pertama
            </button>
        </div>
    @endif
</div>

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

<!-- Modal Tambah Hotkey -->
<div id="add-hotkey-modal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div class="card card-neon" style="width: 100%; max-width: 480px;">
        <div class="card-header">
            <h3 class="card-title">Tambah Pemetaan Hotkey</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('add-hotkey-modal').style.display='none'">✕</button>
        </div>

        <form method="POST" action="{{ route('hotkeys.store') }}">
            @csrf

            <div class="form-group">
                <label for="sound_id" class="form-label">Pilih Efek Suara *</label>
                <select id="sound_id" name="sound_id" class="form-control" required>
                    <option value="">-- Pilih Suara --</option>
                    @foreach($availableSounds as $snd)
                        <option value="{{ $snd->id }}">
                            {{ $snd->title }} ({{ $snd->status === 'public' ? 'Publik' : 'Privat' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="key_combo" class="form-label">Tombol Keyboard *</label>
                <div style="display: flex; gap: 8px;">
                    <input 
                        type="text" 
                        id="key_combo" 
                        name="key_combo" 
                        class="form-control" 
                        placeholder="Contoh: 1, 2, Q, F1, Alt+Z" 
                        required 
                        autocomplete="off"
                    >
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-detect-key" onclick="startKeyDetection()">
                        Deteksi Tombol
                    </button>
                </div>
                <div id="detect-hint" style="font-size: 11px; color: var(--accent-cyan); margin-top: 6px; display: none;">
                    👉 Silakan tekan tombol keyboard Anda sekarang...
                </div>
            </div>

            <div class="form-group">
                <label for="label" class="form-label">Label Kustom (Opsional)</label>
                <input type="text" id="label" name="label" class="form-control" placeholder="Contoh: Suara Menang Game">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('add-hotkey-modal').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Hotkey ⌨️</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Data hotkeys yang aktif untuk listener
    const activeHotkeys = {
        @foreach($hotkeys as $hk)
            @if($hk->sound)
                "{{ strtoupper(trim($hk->key_combo)) }}": {
                    id: {{ $hk->sound->id }},
                    url: "{{ route('sounds.play', $hk->sound) }}",
                    title: "{{ addslashes($hk->sound->title) }}"
                },
            @endif
        @endforeach
    };

    let isDetecting = false;
    let currentAudio = null;
    let currentSoundId = null;
    let masterVolume = (document.getElementById('master-volume-slider')?.value || 80) / 100;

    // In-browser Hotkey Listener
    window.addEventListener('keydown', function(e) {
        // Jangan trigger jika user sedang mengetik di input field atau textarea
        const tag = e.target.tagName.toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select') {
            if (isDetecting && e.target.id === 'key_combo') {
                e.preventDefault();
                let keyStr = '';
                if (e.ctrlKey && e.key !== 'Control') keyStr += 'Ctrl+';
                if (e.altKey && e.key !== 'Alt') keyStr += 'Alt+';
                if (e.shiftKey && e.key !== 'Shift') keyStr += 'Shift+';
                keyStr += (e.key.length === 1 ? e.key.toUpperCase() : e.key);
                document.getElementById('key_combo').value = keyStr;
                stopKeyDetection();
            }
            return;
        }

        // Susun nama kombinasi tombol
        let combo = '';
        if (e.ctrlKey) combo += 'CTRL+';
        if (e.altKey) combo += 'ALT+';
        if (e.shiftKey) combo += 'SHIFT+';
        combo += (e.key.length === 1 ? e.key.toUpperCase() : e.key.toUpperCase());

        if (activeHotkeys[combo]) {
            e.preventDefault();
            const sound = activeHotkeys[combo];
            playAudio(sound.id, sound.url, sound.title);
        }
    });

    function startKeyDetection() {
        isDetecting = true;
        const keyInput = document.getElementById('key_combo');
        const hint = document.getElementById('detect-hint');
        keyInput.value = '';
        keyInput.focus();
        hint.style.display = 'block';
    }

    function stopKeyDetection() {
        isDetecting = false;
        document.getElementById('detect-hint').style.display = 'none';
    }

    function playAudio(soundId, audioUrl, title) {
        if (currentSoundId === soundId && currentAudio && !currentAudio.paused) {
            stopAllAudio();
            return;
        }

        stopAllAudio();

        const playerBar = document.getElementById('global-player-bar');
        const playerTitle = document.getElementById('player-title');
        const playerStatus = document.getElementById('player-status');

        currentAudio = new Audio(audioUrl);
        currentAudio.volume = masterVolume;
        currentSoundId = soundId;

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

        currentSoundId = null;
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
