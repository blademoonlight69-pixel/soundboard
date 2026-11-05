/**
 * MiniSoundboard Gaming Edition — Central Audio & Hotkey Engine
 * Menangani pemutaran audio, Stop All instan, master volume, dan listener keyboard in-browser.
 */
(function() {
    window.Soundboard = window.Soundboard || {};
    window.__activeAudios = window.__activeAudios || [];
    window.__currentSoundId = null;

    // Ambil konfigurasi dari window jika ada, atau gunakan default
    const config = window.SOUNDBOARD_CONFIG || {
        stopAllHotkey: 'End',
        masterVolume: 80,
        hotkeys: {}
    };

    let masterVolume = (config.masterVolume || 80) / 100;
    let isDetectingKey = false;
    let detectingTargetInput = null;
    let detectingHintElem = null;

    /**
     * Hentikan seluruh audio yang sedang berjalan seketika.
     * Membersihkan seluruh instance audio dari memori dan mereset status tampilan tombol.
     */
    function stopAllAudio() {
        // Hentikan dan reset semua audio yang sedang aktif
        if (window.__activeAudios && window.__activeAudios.length > 0) {
            window.__activeAudios.forEach(function(audio) {
                try {
                    audio.pause();
                    audio.currentTime = 0;
                    audio.src = '';
                } catch (e) {
                    console.warn('Error stopping audio:', e);
                }
            });
            window.__activeAudios = [];
        }

        // Reset semua tombol play pada card suara
        document.querySelectorAll('.btn-play').forEach(function(btn) {
            btn.innerHTML = '▶';
            btn.classList.remove('playing');
        });

        // Reset semua class playing pada kartu suara
        document.querySelectorAll('.sound-card').forEach(function(card) {
            card.classList.remove('playing');
        });

        window.__currentSoundId = null;

        // Perbarui Player Bar jika ada di halaman
        const playerStatus = document.getElementById('player-status');
        if (playerStatus) {
            playerStatus.textContent = '⏹️ Dihentikan (Stop All)';
        }

        // Tampilkan indikator status Stop All sekejap
        showToast('⏹️ Stop All: Seluruh pemutaran audio telah dihentikan.', 'danger');
    }

    /**
     * Putar audio dari URL yang diberikan.
     */
    function playAudio(soundId, audioUrl, title) {
        // Jika suara yang sama sedang diputar, klik kedua berfungsi sebagai toggle stop
        if (window.__currentSoundId === soundId && window.__activeAudios.length > 0) {
            stopAllAudio();
            return;
        }

        // Hentikan audio sebelumnya untuk mencegah overlapping
        stopAllAudio();

        const playerBar = document.getElementById('global-player-bar');
        const playerTitle = document.getElementById('player-title');
        const playerStatus = document.getElementById('player-status');
        const playBtn = document.getElementById('play-btn-' + soundId);
        const soundCard = document.getElementById('sound-card-' + soundId);

        try {
            const audio = new Audio(audioUrl);
            audio.volume = masterVolume;
            window.__activeAudios.push(audio);
            window.__currentSoundId = soundId;

            if (playBtn) playBtn.innerHTML = '⏸';
            if (soundCard) soundCard.classList.add('playing');
            if (playerBar) playerBar.style.display = 'flex';
            if (playerTitle) playerTitle.textContent = title || 'Memutar Suara';
            if (playerStatus) playerStatus.textContent = '⚡ Sedang memutar...';

            const playPromise = audio.play();
            if (playPromise !== undefined) {
                playPromise.catch(function(err) {
                    console.error('Audio playback error:', err);
                    if (playerStatus) playerStatus.textContent = '⚠️ Gagal memutar audio';
                    stopAllAudio();
                });
            }

            audio.onended = function() {
                stopAllAudio();
                if (playerStatus) playerStatus.textContent = '✓ Selesai';
            };
        } catch (e) {
            console.error('Failed to instantiate Audio:', e);
            stopAllAudio();
        }
    }

    /**
     * Ubah master volume.
     */
    function changeMasterVolume(val) {
        masterVolume = Math.max(0, Math.min(100, val)) / 100;
        const volumeLabel = document.getElementById('volume-label');
        if (volumeLabel) {
            volumeLabel.textContent = val + '%';
        }
        if (window.__activeAudios) {
            window.__activeAudios.forEach(function(audio) {
                try { audio.volume = masterVolume; } catch (e) {}
            });
        }
    }

    /**
     * Format tombol keyboard dari event keydown menjadi format standar (contoh: "Ctrl+1", "End", "F1").
     */
    function normalizeKeyEvent(e) {
        let key = e.key;

        // Normalisasi nama key khusus
        if (key === ' ') key = 'Space';
        if (key === 'Escape') key = 'Esc';

        // Hanya sertakan modifier jika tombol utama bukan tombol modifier itu sendiri
        let parts = [];
        if (e.ctrlKey && key !== 'Control') parts.push('Ctrl');
        if (e.altKey && key !== 'Alt') parts.push('Alt');
        if (e.shiftKey && key !== 'Shift') parts.push('Shift');

        // Tombol utama
        if (key.length === 1) {
            parts.push(key.toUpperCase());
        } else if (!['Control', 'Alt', 'Shift', 'Meta'].includes(key)) {
            parts.push(key);
        }

        return parts.join('+');
    }

    /**
     * Memulai deteksi perekaman tombol keyboard.
     */
    function startKeyDetection(inputId, hintId) {
        isDetectingKey = true;
        detectingTargetInput = document.getElementById(inputId);
        detectingHintElem = document.getElementById(hintId);

        if (detectingTargetInput) {
            detectingTargetInput.value = '';
            detectingTargetInput.placeholder = 'Tekan tombol keyboard sekarang...';
            detectingTargetInput.focus();
        }
        if (detectingHintElem) {
            detectingHintElem.style.display = 'block';
        }
    }

    /**
     * Menghentikan deteksi perekaman tombol keyboard.
     */
    function stopKeyDetection() {
        isDetectingKey = false;
        if (detectingHintElem) {
            detectingHintElem.style.display = 'none';
        }
        if (detectingTargetInput && !detectingTargetInput.value) {
            detectingTargetInput.placeholder = 'Contoh: End, F1, 1, Alt+Z';
        }
        detectingTargetInput = null;
        detectingHintElem = null;
    }

    /**
     * Tampilkan toast notification ringkas.
     */
    function showToast(message, type) {
        let toast = document.getElementById('soundboard-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'soundboard-toast';
            toast.style.cssText = 'position: fixed; bottom: 84px; right: 24px; padding: 12px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; z-index: 9999; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5); transition: opacity 0.3s ease, transform 0.3s ease; opacity: 0; pointer-events: none; backdrop-filter: blur(10px);';
            document.body.appendChild(toast);
        }

        if (type === 'danger') {
            toast.style.background = 'rgba(239, 68, 68, 0.95)';
            toast.style.color = '#ffffff';
            toast.style.border = '1px solid #f87171';
        } else {
            toast.style.background = 'rgba(0, 245, 255, 0.95)';
            toast.style.color = '#060611';
            toast.style.border = '1px solid #00f5ff';
        }

        toast.textContent = message;
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';

        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
        }, 2200);
    }

    // In-browser Keydown Listener
    window.addEventListener('keydown', function(e) {
        // Mode deteksi tombol untuk form input
        if (isDetectingKey && detectingTargetInput) {
            // Abaikan jika hanya tombol modifier tunggal yang ditekan
            if (['Control', 'Alt', 'Shift', 'Meta'].includes(e.key)) {
                return;
            }
            e.preventDefault();
            const combo = normalizeKeyEvent(e);
            if (combo) {
                detectingTargetInput.value = combo;
                stopKeyDetection();
            }
            return;
        }

        // Jangan picu hotkey jika user sedang mengetik di elemen form biasa
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) {
            return;
        }

        const currentStopAll = (window.SOUNDBOARD_CONFIG && window.SOUNDBOARD_CONFIG.stopAllHotkey)
            ? window.SOUNDBOARD_CONFIG.stopAllHotkey.trim().toUpperCase()
            : 'END';

        const pressedKey = e.key.toUpperCase();
        const fullCombo = normalizeKeyEvent(e).toUpperCase();

        // 1. Cek apakah tombol yang ditekan adalah Stop All Hotkey (contoh: "END")
        if (pressedKey === currentStopAll || fullCombo === currentStopAll) {
            e.preventDefault();
            stopAllAudio();
            return;
        }

        // 2. Cek apakah tombol yang ditekan terdaftar pada hotkey suara
        const activeHotkeys = (window.SOUNDBOARD_CONFIG && window.SOUNDBOARD_CONFIG.hotkeys) || {};
        if (activeHotkeys[fullCombo]) {
            e.preventDefault();
            const item = activeHotkeys[fullCombo];
            playAudio(item.id, item.url, item.title);
        }
    });

    // Ekspor fungsi ke global namespace
    window.Soundboard = {
        playAudio: playAudio,
        stopAllAudio: stopAllAudio,
        changeMasterVolume: changeMasterVolume,
        startKeyDetection: startKeyDetection,
        stopKeyDetection: stopKeyDetection,
        showToast: showToast
    };

    // Backward-compatibility aliases
    window.playAudio = playAudio;
    window.stopAllAudio = stopAllAudio;
    window.changeMasterVolume = changeMasterVolume;
    window.startKeyDetection = startKeyDetection;
    window.stopKeyDetection = stopKeyDetection;
})();
