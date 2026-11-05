const { ipcRenderer } = require('electron');

let currentAudios = [];
let masterVolume = 0.8;
let currentSettings = { stop_all_hotkey: 'End' };
let currentHotkeys = [];


// Audio Player Object
let audioRequestController = null;
let audioRequestId = 0;
let currentObjectUrl = null;

window.CompanionPlayer = {
    play: async function (soundUrl, title) {
        // Hentikan audio dan request sebelumnya
        this.stopAll();

        const requestId = audioRequestId;
        const email = localStorage.getItem('mb_email');
        const token = localStorage.getItem('mb_sync_token');

        if (!email || !token) {
            console.error('[AUDIO] Email atau token tidak ditemukan.');
            document.getElementById('np-status').textContent =
                'Gagal: autentikasi tidak ditemukan';
            return;
        }

        audioRequestController = new AbortController();

        try {
            console.log('[AUDIO] Mengambil audio:', soundUrl);

            const response = await fetch(soundUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'audio/*',
                    'X-User-Email': email,
                    'X-Sync-Token': token
                },
                signal: audioRequestController.signal
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(
                    `HTTP ${response.status}: ${errorText}`
                );
            }

            const audioBlob = await response.blob();

            // Jangan putar audio jika sudah dihentikan
            if (requestId !== audioRequestId) return;

            currentObjectUrl = URL.createObjectURL(audioBlob);

            const audio = new Audio(currentObjectUrl);
            audio.volume = masterVolume;
            currentAudios.push(audio);

            document.getElementById('np-status').textContent =
                '⚡ Sedang memutar audio...';

            document.getElementById('np-title').textContent =
                title || 'Efek Suara';

            audio.onended = () => {
                if (requestId === audioRequestId) {
                    this.stopAll();
                    document.getElementById('np-status').textContent =
                        '✓ Selesai';
                }
            };

            audio.onerror = () => {
                console.error('[AUDIO] Gagal membaca file audio.');
                if (requestId === audioRequestId) {
                    this.stopAll();
                    document.getElementById('np-status').textContent =
                        'Gagal membaca audio';
                }
            };

            await audio.play();

            console.log('[AUDIO] Berhasil memutar:', title);

        } catch (error) {
            if (error.name === 'AbortError') return;

            console.error('[AUDIO] Playback gagal:', error);

            if (requestId === audioRequestId) {
                this.stopAll();
                document.getElementById('np-status').textContent =
                    '⚠️ Gagal memutar audio';
            }
        }
    },

    stopAll: function () {
        // Batalkan pengunduhan audio yang masih berlangsung
        audioRequestId++;

        if (audioRequestController) {
            audioRequestController.abort();
            audioRequestController = null;
        }

        // Hentikan semua audio aktif
        currentAudios.forEach(audio => {
            try {
                audio.pause();
                audio.currentTime = 0;
                audio.src = '';
            } catch (error) { }
        });

        currentAudios = [];

        // Hapus URL Blob
        if (currentObjectUrl) {
            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        }

        document.getElementById('np-status').textContent =
            'Dihentikan (Stop All)';

        document.getElementById('np-title').textContent =
            'Tidak ada suara aktif';
    }
};


// Listener IPC dari Main Process (Trigger dari Windows Global Hotkey)
ipcRenderer.on('companion:play-sound', (event, item) => {
    console.log('[IPC] Playing sound via global hotkey:', item);
    window.CompanionPlayer.play(item.play_url, item.sound_title);
});

ipcRenderer.on('companion:stop-all', () => {
    console.log('[IPC] Stop All triggered globally!');
    window.CompanionPlayer.stopAll();
});

ipcRenderer.on('companion:request-sync', () => {
    syncHotkeys();
});

// UI Event Listeners
document.getElementById('btn-minimize-tray').addEventListener('click', () => {
    ipcRenderer.send('companion:minimize-to-tray');
});

document.getElementById('btn-stop-all-manual').addEventListener('click', () => {
    window.CompanionPlayer.stopAll();
});

document.getElementById('volume-slider').addEventListener('input', (e) => {
    const val = parseInt(e.target.value, 10);
    masterVolume = val / 100;
    document.getElementById('volume-text').textContent = val + '%';
    currentAudios.forEach(a => {
        try { a.volume = masterVolume; } catch (e) { }
    });
});

// Sync & Login Logic
async function loginAndSync() {
    const serverUrl = document.getElementById('server-url').value.trim().replace(/\/$/, '');
    const email = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;

    if (!serverUrl || !email || !password) {
        alert('Harap isi Server URL, Email, dan Kata Sandi.');
        return;
    }

    const btn = document.getElementById('btn-login-sync');
    btn.disabled = true;
    btn.textContent = 'Menghubungkan...';

    try {
        const response = await fetch(`${serverUrl}/api/desktop/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            // Simpan credentials di localStorage untuk auto-sync berikutnya
            localStorage.setItem('mb_server_url', serverUrl);
            localStorage.setItem('mb_email', email);
            localStorage.setItem('mb_sync_token', data.sync_token);

            applySyncData(data);
            alert(`Berhasil terhubung sebagai ${data.user.name}! Global hotkey kini aktif di Windows.`);
        } else {
            alert('Gagal menghubungkan: ' + (data.message || 'Kredensial salah.'));
        }
    } catch (err) {
        console.error('Login error:', err);
        alert('Gagal menghubungi server. Pastikan URL server aktif dan dapat diakses.');
    } finally {
        btn.disabled = false;
        btn.textContent = '⚡ Masuk & Sinkronkan Hotkey';
    }
}

async function syncHotkeys() {
    const serverUrl = localStorage.getItem('mb_server_url') || document.getElementById('server-url').value.trim().replace(/\/$/, '');
    const email = localStorage.getItem('mb_email') || document.getElementById('login-email').value.trim();
    const token = localStorage.getItem('mb_sync_token');

    if (!token) {
        return loginAndSync();
    }

    try {
        const response = await fetch(`${serverUrl}/api/desktop/hotkeys`, {
            headers: {
                'Accept': 'application/json',
                'X-Sync-Token': token,
                'X-User-Email': email
            }
        });

        const data = await response.json();

        if (response.ok && data.status === 'success') {
            applySyncData(data);
        } else {
            console.warn('Sync token invalid, attempting re-login.');
        }
    } catch (err) {
        console.error('Sync failed:', err);
    }
}

function applySyncData(data) {
    currentSettings = data.settings || { stop_all_hotkey: 'End', master_volume: 80 };
    currentHotkeys = data.hotkeys || [];

    // Tampilkan stop all key
    const stopAllKey = currentSettings.stop_all_hotkey || 'End';
    document.getElementById('display-stop-all').textContent = stopAllKey.toUpperCase();

    // Tampilkan email user
    if (data.user && data.user.email) {
        document.getElementById('connected-user-email').textContent = `● ${data.user.name} (${data.user.email})`;
        document.getElementById('connected-user-email').style.color = 'var(--accent-green)';
    }

    // Set master volume
    if (currentSettings.master_volume !== undefined) {
        const vol = currentSettings.master_volume;
        masterVolume = vol / 100;
        document.getElementById('volume-slider').value = vol;
        document.getElementById('volume-text').textContent = vol + '%';
    }

    // Render daftar hotkey
    renderHotkeysList(currentHotkeys);

    // Kirim ke main process Electron untuk didaftarkan ke Windows Global Keyboard Hook
    ipcRenderer.send('companion:apply-hotkeys', {
        settings: currentSettings,
        hotkeys: currentHotkeys
    });
}

function renderHotkeysList(hotkeys) {
    const container = document.getElementById('hotkeys-list');
    const countBadge = document.getElementById('hotkeys-count');

    container.replaceChildren();

    if (!hotkeys || hotkeys.length === 0) {
        const message = document.createElement('div');
        message.style.cssText =
            'text-align:center;padding:20px;color:var(--text-muted);font-size:12px;';
        message.textContent =
            'Belum ada hotkey suara yang dipetakan di website.';

        container.appendChild(message);
        countBadge.textContent = '0 Terdaftar';
        return;
    }

    countBadge.textContent = `${hotkeys.length} Terdaftar`;

    hotkeys.forEach(hk => {
        const row = document.createElement('div');
        row.className = 'hotkey-row';

        const info = document.createElement('div');
        info.style.cssText =
            'display:flex;align-items:center;gap:8px;';

        const key = document.createElement('kbd');
        key.textContent = hk.key_combo;

        const title = document.createElement('span');
        title.style.cssText =
            'font-size:12px;font-weight:600;color:#fff;';
        title.textContent = hk.sound_title;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-secondary';
        button.style.cssText =
            'padding:2px 8px;font-size:11px;';
        button.textContent = '▶ Test';

        button.addEventListener('click', () => {
            window.CompanionPlayer.play(
                hk.play_url,
                hk.sound_title
            );
        });

        info.append(key, title);
        row.append(info, button);
        container.appendChild(row);
    });
}

// Attach sync buttons
document.getElementById('btn-login-sync').addEventListener('click', loginAndSync);
document.getElementById('btn-sync-now').addEventListener('click', syncHotkeys);

// Inisialisasi saat window dibuka
window.addEventListener('DOMContentLoaded', () => {
    const savedUrl = localStorage.getItem('mb_server_url');
    const savedEmail = localStorage.getItem('mb_email');

    if (savedUrl) document.getElementById('server-url').value = savedUrl;
    if (savedEmail) document.getElementById('login-email').value = savedEmail;

    if (localStorage.getItem('mb_sync_token')) {
        syncHotkeys();
    }
});
