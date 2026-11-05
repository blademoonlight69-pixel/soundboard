@extends('layouts.app')

@section('title', 'Hotkey Manager — MiniSoundboard Gaming Edition')
@section('page_title', '⌨️ Hotkey Manager')
@section('page_subtitle', 'Petakan tombol keyboard ke efek suara favorit Anda dan kelola hotkey Stop All darurat.')

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
                    Tekan tombol yang telah dipetakan pada keyboard kapan saja saat tab ini aktif untuk memutar suara atau menghentikan audio!
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('change-stop-all-modal').style.display='flex'">
                ⚙️ Atur Stop All Hotkey
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('add-hotkey-modal').style.display='flex'">
                + Tambah Hotkey Suara
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     STOP ALL HOTKEY CARD (PENGATURAN BAWAAN & PERSONAL USER)
     ============================================================ -->
<div class="card card-neon mb-24" style="border-color: rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.04);">
    <div class="card-header" style="margin-bottom: 12px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 24px;">⏹️</span>
            <div>
                <h3 class="card-title" style="color: #fca5a5; font-size: 16px;">Hotkey Stop All Bawaan Sistem</h3>
                <p style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                    Menghentikan seluruh pemutaran audio seketika dari browser maupun aplikasi game.
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span style="font-size: 12px; color: var(--text-muted);">Tombol Aktif Anda:</span>
            <kbd style="background: rgba(239, 68, 68, 0.2); border: 2px solid var(--accent-red); color: #ffffff; padding: 6px 16px; border-radius: var(--radius-sm); font-family: var(--font-display); font-size: 16px; font-weight: 800; box-shadow: 0 0 15px rgba(239, 68, 68, 0.35); letter-spacing: 0.05em;">
                {{ $stopAllHotkey }}
            </kbd>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid rgba(239, 68, 68, 0.2); padding-top: 14px; margin-top: 6px;">
        <div style="font-size: 12px; color: var(--text-secondary); max-width: 580px; line-height: 1.6;">
            💡 <strong>Bawaan Sistem:</strong> Tombol default adalah <code style="color: #fca5a5; font-weight: bold;">End</code>. Pengaturan ini tersimpan di akun Anda dan tidak memengaruhi pengguna lain. Suara yang sedang berjalan tidak akan bocor ke background setelah ditekan.
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            @if(strtoupper($stopAllHotkey) !== 'END')
                <form method="POST" action="{{ route('hotkeys.stop-all.reset') }}" style="display: inline;" onsubmit="return confirm('Kembalikan tombol Stop All ke default sistem [End]?')">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="border-color: rgba(239, 68, 68, 0.4); color: #fca5a5;">
                        🔄 Reset ke Default (End)
                    </button>
                </form>
            @endif

            <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('change-stop-all-modal').style.display='flex'">
                ✏️ Ganti Tombol Stop All
            </button>
        </div>
    </div>
</div>

<!-- Hotkey List -->
<div class="card mb-24">
    <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 class="card-title">Daftar Hotkey Suara Terdaftar ({{ $hotkeys->count() }})</h3>
            <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                Semua pemetaan tombol suara di bawah ini terlindungi dari konflik dengan tombol Stop All [{{ $stopAllHotkey }}].
            </p>
        </div>
        <span class="badge badge-user">In-Browser & Desktop Ready</span>
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

<!-- ============================================================
     MODAL GANTI STOP ALL HOTKEY
     ============================================================ -->
<div id="change-stop-all-modal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h3 class="modal-title" style="color: #fca5a5;">⏹️ Pengaturan Hotkey Stop All</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('change-stop-all-modal').style.display='none'">✕</button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('hotkeys.stop-all.update') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">Tombol Stop All Saat Ini</label>
                    <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">
                        Saat ini diatur ke: <kbd style="background: rgba(239, 68, 68, 0.2); border: 1px solid var(--accent-red); color: white; padding: 3px 10px; border-radius: 4px;">{{ $stopAllHotkey }}</kbd>
                    </div>
                </div>

                <div class="form-group">
                    <label for="stop_all_hotkey_input" class="form-label">Tombol Stop All Baru *</label>
                    <div style="display: flex; gap: 8px;">
                        <input
                            type="text"
                            id="stop_all_hotkey_input"
                            name="stop_all_hotkey"
                            class="form-control"
                            value="{{ old('stop_all_hotkey', $stopAllHotkey) }}"
                            placeholder="Contoh: End, F12, Escape, Pause"
                            required
                            autocomplete="off"
                        >
                        <button type="button" class="btn btn-secondary btn-sm" onclick="startKeyDetection('stop_all_hotkey_input', 'stop-all-hint')">
                            Deteksi Tombol
                        </button>
                    </div>
                    <div id="stop-all-hint" style="font-size: 11px; color: var(--accent-cyan); margin-top: 6px; display: none;">
                        👉 Silakan tekan tombol keyboard yang ingin Anda jadikan Stop All sekarang...
                    </div>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 6px;">
                        ⚠️ Catatan: Tombol yang dipilih tidak boleh sama dengan tombol suara yang sudah Anda petakan.
                    </p>
                </div>

                <div class="modal-footer" style="padding: 16px 0 0 0; background: transparent; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('change-stop-all-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-danger">Simpan Stop All Hotkey ⏹️</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL TAMBAH HOTKEY SUARA BARU
     ============================================================ -->
<div id="add-hotkey-modal" class="modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Pemetaan Hotkey Suara</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('add-hotkey-modal').style.display='none'">✕</button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('hotkeys.store') }}" id="form-add-hotkey">
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
                            oninput="checkHotkeyConflict(this.value)"
                        >
                        <button type="button" class="btn btn-secondary btn-sm" id="btn-detect-key" onclick="startKeyDetection('key_combo', 'detect-hint')">
                            Deteksi Tombol
                        </button>
                    </div>
                    <div id="detect-hint" style="font-size: 11px; color: var(--accent-cyan); margin-top: 6px; display: none;">
                        👉 Silakan tekan tombol keyboard Anda sekarang...
                    </div>
                    <div id="conflict-warning" style="display: none; font-size: 12px; color: #f87171; margin-top: 6px; font-weight: 600;">
                        ⚠️ Peringatan: Tombol ini sedang digunakan sebagai Hotkey Stop All! Harap pilih tombol lain.
                    </div>
                </div>

                <div class="form-group">
                    <label for="label" class="form-label">Label Kustom (Opsional)</label>
                    <input type="text" id="label" name="label" class="form-control" placeholder="Contoh: Suara Menang Game">
                </div>

                <div class="modal-footer" style="padding: 16px 0 0 0; background: transparent; border-top: 1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('add-hotkey-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-hotkey">Simpan Hotkey ⌨️</button>
                </div>
            </form>
        </div>
    </div>
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
        <button type="button" class="btn btn-danger btn-sm" onclick="stopAllAudio()" title="Hentikan Semua Audio (Stop All [{{ $stopAllHotkey }}])">
            <span>⏹️</span>
            <span>Stop All [{{ $stopAllHotkey }}]</span>
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
        stopAllHotkey: "{{ addslashes($stopAllHotkey ?? 'End') }}",
        masterVolume: {{ auth()->user()->settings->master_volume ?? 80 }},
        hotkeys: {
            @foreach($hotkeys as $hk)
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

    function checkHotkeyConflict(val) {
        const stopAllKey = "{{ strtoupper(trim($stopAllHotkey ?? 'End')) }}";
        const cleanVal = (val || '').trim().toUpperCase();
        const warning = document.getElementById('conflict-warning');
        const submitBtn = document.getElementById('btn-submit-hotkey');

        if (cleanVal === stopAllKey) {
            warning.style.display = 'block';
            submitBtn.disabled = true;
        } else {
            warning.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
</script>
<script src="{{ asset('js/soundboard-player.js') }}"></script>
@endsection
