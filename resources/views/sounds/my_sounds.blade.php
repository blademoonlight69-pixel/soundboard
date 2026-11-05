@extends('layouts.app')

@section('title', 'Koleksi Suara Saya — MiniSoundboard Gaming Edition')
@section('page_title', '📁 Koleksi Suara Saya')
@section('page_subtitle', 'Kelola semua efek suara yang telah Anda unggah, atur privasi, dan pantau status moderasi.')

@section('content')
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total Koleksi</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: var(--accent-green);">{{ $stats['public'] }}</div>
        <div class="stat-label">Suara Publik</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: var(--text-secondary);">{{ $stats['private'] }}</div>
        <div class="stat-label">Suara Privat</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color: var(--accent-red);">{{ $stats['hidden'] }}</div>
        <div class="stat-label">Dimoderasi (Hidden)</div>
    </div>
</div>

<!-- Filter Bar & Upload Button -->
<div class="card mb-24" style="padding: 18px 24px;">
    <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('my-sounds') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-ghost' }}">
                Semua ({{ $stats['total'] }})
            </a>
            <a href="{{ route('my-sounds', ['status' => 'public']) }}" class="btn btn-sm {{ request('status') === 'public' ? 'btn-primary' : 'btn-ghost' }}">
                🌐 Publik ({{ $stats['public'] }})
            </a>
            <a href="{{ route('my-sounds', ['status' => 'private']) }}" class="btn btn-sm {{ request('status') === 'private' ? 'btn-primary' : 'btn-ghost' }}">
                🔒 Privat ({{ $stats['private'] }})
            </a>
            @if($stats['hidden'] > 0)
                <a href="{{ route('my-sounds', ['status' => 'hidden']) }}" class="btn btn-sm {{ request('status') === 'hidden' ? 'btn-danger' : 'btn-ghost' }}">
                    ⚠️ Dimoderasi ({{ $stats['hidden'] }})
                </a>
            @endif
        </div>

        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('upload-modal').style.display='flex'">
            <span>+ Upload Suara Baru</span>
        </button>
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

                    <!-- Status Badge -->
                    <div>
                        @if($sound->isPublic())
                            <span class="badge badge-public">🌐 Publik</span>
                        @elseif($sound->isPrivate())
                            <span class="badge badge-private">🔒 Privat</span>
                        @elseif($sound->isHidden())
                            <span class="badge badge-hidden">⚠️ Hidden</span>
                        @endif
                    </div>
                </div>

                <div class="sound-card-title" title="{{ $sound->title }}">{{ $sound->title }}</div>

                @if($sound->isHidden() && $sound->hidden_reason)
                    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); padding: 6px 10px; border-radius: var(--radius-sm); font-size: 11px; color: #fca5a5; margin-bottom: 8px;">
                        <strong>Catatan Admin:</strong> {{ $sound->hidden_reason }}
                    </div>
                @endif

                <div class="sound-card-meta">
                    @if($sound->category)
                        <span class="sound-card-category">{{ $sound->category->icon }} {{ $sound->category->name }}</span>
                    @endif
                    <span>▶ {{ number_format($sound->play_count) }}x</span>
                    <span>💾 {{ $sound->file_size_human }}</span>
                </div>

                <!-- Action Buttons: Edit, Delete, Map to Hotkey -->
                <div style="display: flex; gap: 6px; margin-top: 14px; border-top: 1px solid var(--border); padding-top: 10px;">
                    <button 
                        type="button" 
                        class="btn btn-ghost btn-sm" 
                        style="padding: 4px 8px; font-size: 11px; flex: 1;"
                        onclick="openEditModal({{ $sound->id }}, '{{ addslashes($sound->title) }}', '{{ addslashes($sound->description ?? '') }}', '{{ $sound->category_id }}', '{{ $sound->status }}')"
                    >
                        ✏️ Edit
                    </button>

                    <form method="POST" action="{{ route('sounds.destroy', $sound) }}" style="display: inline;" onsubmit="return confirm('Hapus suara ini secara permanen?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 11px;">
                            🗑️
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <div style="margin-top: 24px;">
        {{ $sounds->links() }}
    </div>
@else
    <div class="empty-state">
        <span class="empty-state-icon">📁</span>
        <h3 class="empty-state-title">Koleksi Masih Kosong</h3>
        <p class="empty-state-desc">Anda belum memiliki suara yang sesuai dengan filter ini. Mulai unggah efek suara favorit Anda sekarang!</p>
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
            <h3 class="modal-title">Upload Suara Baru</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('upload-modal').style.display='none'">✕</button>
        </div>

        <div class="modal-body">
            <form method="POST" action="{{ route('sounds.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="upload-title" class="form-label">Judul Suara *</label>
                    <input type="text" id="upload-title" name="title" class="form-control" placeholder="Contoh: Sound Kemenangan..." required>
                </div>

                <div class="form-group">
                    <label for="upload-category" class="form-label">Kategori</label>
                    <select id="upload-category" name="category_id" class="form-control">
                        <option value="">Pilih Kategori (Opsional)</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="upload-status" class="form-label">Visibilitas Akses *</label>
                    <select id="upload-status" name="status" class="form-control" required>
                        <option value="private">🔒 Privat (Hanya saya yang dapat memutar)</option>
                        <option value="public">🌐 Publik (Muncul di katalog publik)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="upload-desc" class="form-label">Deskripsi Singkat</label>
                    <textarea id="upload-desc" name="description" class="form-control" placeholder="Deskripsi opsional..." rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label for="upload-audio" class="form-label">File Audio (MP3, WAV, OGG, FLAC maks 10MB) *</label>
                    <input type="file" id="upload-audio" name="audio" class="form-control" accept="audio/*" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('upload-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Unggah 🚀</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Suara -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Suara</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('edit-modal').style.display='none'">✕</button>
        </div>

        <div class="modal-body">
            <form id="edit-form" method="POST" action="">
                @csrf
                @method('PATCH')

                <div class="form-group">
                    <label for="edit-title" class="form-label">Judul Suara *</label>
                    <input type="text" id="edit-title" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit-category" class="form-label">Kategori</label>
                    <select id="edit-category" name="category_id" class="form-control">
                        <option value="">Pilih Kategori (Opsional)</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit-status" class="form-label">Visibilitas Akses *</label>
                    <select id="edit-status" name="status" class="form-control" required>
                        <option value="private">🔒 Privat (Hanya saya)</option>
                        <option value="public">🌐 Publik (Katalog publik)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit-desc" class="form-label">Deskripsi</label>
                    <textarea id="edit-desc" name="description" class="form-control" rows="2"></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('edit-modal').style.display='none'">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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

    function openEditModal(id, title, desc, categoryId, status) {
        document.getElementById('edit-form').action = '/sounds/' + id;
        document.getElementById('edit-title').value = title;
        document.getElementById('edit-desc').value = desc;
        document.getElementById('edit-category').value = categoryId || '';
        document.getElementById('edit-status').value = status;
        document.getElementById('edit-modal').style.display = 'flex';
    }
</script>
<script src="{{ asset('js/soundboard-player.js') }}"></script>
@endsection
