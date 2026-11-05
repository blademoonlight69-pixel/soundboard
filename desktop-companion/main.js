const electron = require('electron');
console.log('electron export type in Electron runtime:', typeof electron);
console.log('electron keys in runtime:', Object.keys(electron).slice(0, 10));
const { app, BrowserWindow, globalShortcut, ipcMain, Tray, Menu, nativeImage, Notification } = electron;

const path = require('path');

let mainWindow = null;
let tray = null;
let activeShortcuts = [];
let currentStopAllKey = 'End';

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 480,
        height: 720,
        resizable: false,
        backgroundColor: '#060611',
        autoHideMenuBar: true,
        title: 'MiniSoundboard Windows Companion',
        webPreferences: {
            nodeIntegration: true,
            contextIsolation: false,
            backgroundThrottling: false, // PENTING: Audio tidak di-throttle saat jendela diminimalkan atau saat main game
            webSecurity: false // Mengizinkan streaming audio dari domain hosting tanpa CORS issue di desktop
        }
    });

    mainWindow.loadFile(path.join(__dirname, 'index.html'));

    // Developer Tools hanya aktif saat pengembangan lokal
if (!app.isPackaged) {
    mainWindow.webContents.openDevTools();
}

    // Jangan matikan app saat ditutup, sembunyikan ke System Tray agar hotkey tetap aktif saat main Roblox
    mainWindow.on('close', (event) => {
        if (!app.isQuitting) {
            event.preventDefault();
            mainWindow.hide();
            if (Notification.isSupported()) {
                new Notification({
                    title: 'MiniSoundboard Tetap Aktif 🎮',
                    body: 'Aplikasi diminimalkan ke System Tray. Global hotkey tetap dapat digunakan saat main Roblox!'
                }).show();
            }
        }
    });
}

function createTray() {
    // Buat icon sederhana untuk tray jika file gambar belum ada
    const iconPath = path.join(__dirname, 'tray-icon.png');
    let trayIcon;
    try {
        trayIcon = nativeImage.createFromPath(iconPath);
        if (trayIcon.isEmpty()) {
            trayIcon = nativeImage.createEmpty();
        }
    } catch (e) {
        trayIcon = nativeImage.createEmpty();
    }

    tray = new Tray(trayIcon);
    updateTrayMenu('Listener Siap');

    tray.setToolTip('MiniSoundboard Gaming Companion — Global Hotkey Active');
    tray.on('double-click', () => {
        if (mainWindow) {
            mainWindow.show();
            mainWindow.focus();
        }
    });
}

function updateTrayMenu(statusText) {
    if (!tray) return;

    const contextMenu = Menu.buildFromTemplate([
        { label: `🎮 MiniSoundboard Companion (${statusText || 'Aktif'})`, enabled: false },
        { label: `⏹️ Stop All Key: [ ${currentStopAllKey} ]`, enabled: false },
        { type: 'separator' },
        {
            label: 'Buka Jendela Companion',
            click: () => {
                if (mainWindow) {
                    mainWindow.show();
                    mainWindow.focus();
                }
            }
        },
        {
            label: '🔄 Sinkronkan Ulang Hotkey',
            click: () => {
                if (mainWindow) {
                    mainWindow.webContents.send('companion:request-sync');
                }
            }
        },
        {
            label: '⏹️ Hentikan Semua Audio (Stop All)',
            click: () => {
                if (mainWindow) {
                    mainWindow.webContents.send('companion:stop-all');
                }
            }
        },
        { type: 'separator' },
        {
            label: 'Keluar Sepenuhnya',
            click: () => {
                app.isQuitting = true;
                app.quit();
            }
        }
    ]);

    tray.setContextMenu(contextMenu);
}

/**
 * Format tombol ke format Accelerator Electron.
 * Contoh: "Ctrl+1" -> "CommandOrControl+1", "Alt+Z" -> "Alt+Z", "End" -> "End"
 */
function toElectronAccelerator(combo) {
    if (!combo) return '';

    return combo
        .trim()
        .replace(/\bArrow(Up|Down|Left|Right)\b/gi, (_, direction) => {
            return direction.charAt(0).toUpperCase() +
                direction.slice(1).toLowerCase();
        })
        .replace(/Ctrl\+/gi, 'CommandOrControl+')
        .replace(/Control\+/gi, 'CommandOrControl+')
        .replace(/Esc/gi, 'Escape');
}

/**
 * Daftarkan shortcut secara global ke OS Windows melalui Win32 RegisterHotKey API bawaan Electron.
 */
function registerGlobalHotkeys(settings, hotkeys) {
    // 1. Bersihkan seluruh shortcut sebelumnya
    globalShortcut.unregisterAll();
    activeShortcuts = [];

    // 2. Daftarkan Stop All Hotkey (Default: 'End' atau kustom user)
    const stopAll = (settings && settings.stop_all_hotkey) ? settings.stop_all_hotkey.trim() : 'End';
    currentStopAllKey = stopAll;
    const stopAllAccelerator = toElectronAccelerator(stopAll);

    try {
        const registered = globalShortcut.register(stopAllAccelerator, () => {
            console.log('[GLOBAL HOTKEY TRIGGERED] Stop All via:', stopAll);
            if (mainWindow) {
                mainWindow.webContents.send('companion:stop-all');
            }
        });

        if (registered) {
            activeShortcuts.push({ key: stopAll, isStopAll: true });
            console.log(`[OK] Global Stop All registered on [${stopAll}]`);
        } else {
            console.warn(`[WARN] Failed to register global shortcut for Stop All [${stopAll}]`);
        }
    } catch (err) {
        console.error('Error registering Stop All shortcut:', err);
    }

    // 3. Daftarkan seluruh hotkey suara
    if (Array.isArray(hotkeys)) {
        hotkeys.forEach((hk) => {
            const accelerator = toElectronAccelerator(hk.key_combo);
            if (!accelerator) return;

            // Jangan izinkan bentrok dengan Stop All
            if (accelerator.toUpperCase() === stopAllAccelerator.toUpperCase()) {
                console.warn(`[CONFLICT] Key [${hk.key_combo}] conflicts with Stop All. Skipping sound hotkey.`);
                return;
            }

            try {
                const ok = globalShortcut.register(accelerator, () => {
                    console.log(`[GLOBAL HOTKEY TRIGGERED] Playing [${hk.sound_title}] via [${hk.key_combo}]`);
                    if (mainWindow) {
                        mainWindow.webContents.send('companion:play-sound', hk);
                    }
                });

                if (ok) {
                    activeShortcuts.push({ key: hk.key_combo, soundTitle: hk.sound_title });
                } else {
                    console.warn(`[WARN] Could not register global shortcut [${hk.key_combo}]`);
                }
            } catch (err) {
                console.error(`Error registering shortcut for ${hk.key_combo}:`, err);
            }
        });
    }

    updateTrayMenu(`${activeShortcuts.length} Hotkey Aktif`);
    console.log(`Total active global hotkeys in Windows: ${activeShortcuts.length}`);
}

// IPC Handlers dari Renderer (UI)
ipcMain.on('companion:apply-hotkeys', (event, { settings, hotkeys }) => {
    registerGlobalHotkeys(settings, hotkeys);
    event.reply('companion:hotkeys-applied', { count: activeShortcuts.length });
});

ipcMain.on('companion:minimize-to-tray', () => {
    if (mainWindow) {
        mainWindow.hide();
    }
});

app.whenReady().then(() => {
    createWindow();
    createTray();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on('will-quit', () => {
    // Unregister semua shortcut saat aplikasi ditutup sepenuhnya
    globalShortcut.unregisterAll();
});
