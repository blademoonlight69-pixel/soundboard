# MiniSoundboard Windows Global Companion

Aplikasi desktop pendamping resmi untuk **MiniSoundboard Gaming Edition**.

Aplikasi ini mengatasi batasan keamanan browser W3C standar dengan mendaftarkan hotkey keyboard langsung ke **Windows Win32 Global Keyboard Hook API** via Electron, sehingga hotkey soundboard dan **Stop All** tetap dapat digunakan saat Anda bermain **Roblox**, **Discord**, game FPS/MOBA layar penuh, atau saat browser diminimalkan.

---

## Fitur Utama

1. **Global Keyboard Hook di Seluruh Windows**:
   - Menangkap tombol keyboard saat Anda berada di dalam game Roblox, browser, emulator, atau aplikasi apapun.
2. **Audio Output Langsung ke Perangkat Windows**:
   - Suara tetap diputar jernih tanpa jeda (*low latency*) melalui perangkat audio default Windows.
3. **Stop All Global (Default: `End`)**:
   - Menekan tombol `End` (atau tombol Stop All kustom akun Anda) langsung menghentikan semua audio yang sedang berjalan tanpa delay.
4. **Sinkronisasi Akun Otomatis**:
   - Cukup login satu kali dengan akun MiniSoundboard Anda.
   - Semua koleksi suara publik, suara privat milik Anda, serta pemetaan hotkey akan otomatis terunduh dan aktif di Windows.
5. **System Tray Integration**:
   - Aplikasi dapat diminimalkan ke System Tray sehingga tidak mengganggu layar permainan.
   - Klik kanan ikon tray untuk menghentikan suara, sync ulang, atau membuka jendela kontrol.

---

## Cara Menjalankan

### Cara Cepat (Windows):
1. Buka folder `desktop-companion`.
2. Klik dua kali file `run-companion.bat`.
3. Aplikasi akan mengunduh paket Electron (jika belum ada) dan langsung membuka jendela pendamping.

### Cara Manual via Terminal:
```bash
cd desktop-companion
npm install
npm start
```

---

## Cara Menghubungkan ke Website

1. Pada input **URL Server Soundboard**:
   - Jika berjalan lokal: `http://localhost/Soundboard/public`
   - Jika menggunakan hosting online EthioDeploy: Masukkan URL website Anda (contoh: `https://soundboard.ethiodeploy.app`).
2. Masukkan **Email** dan **Kata Sandi** akun Anda.
3. Klik tombol **⚡ Masuk & Sinkronkan Hotkey**.
4. Selesai! Buka game Roblox atau aplikasi game favorit Anda, lalu tekan tombol hotkey yang sudah Anda petakan.
