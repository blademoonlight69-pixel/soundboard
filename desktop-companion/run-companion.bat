@echo off
title MiniSoundboard — Windows Global Hotkey Companion
color 0b
echo ============================================================
echo   MiniSoundboard Gaming Edition — Windows Global Companion
echo   Mendukung pemutaran audio global saat bermain Roblox, Discord, dsb.
echo ============================================================
echo.

cd /d "%~dp0"

if not exist node_modules (
    echo [INFO] Menginstal dependensi Electron pertama kali...
    call npm install
    if errorlevel 1 (
        echo [ERROR] Gagal menginstal dependensi. Pastikan koneksi internet aktif.
        pause
        exit /b 1
    )
)

echo [OK] Menjalankan MiniSoundboard Companion...
call npx electron .
if errorlevel 1 (
    echo.
    echo [PERINGATAN] Aplikasi ditutup atau terjadi error.
    pause
)
