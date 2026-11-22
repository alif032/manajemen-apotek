#!/usr/bin/env bash
set -e

# Sesuaikan port jika penyedia hosting menyuntikkan variabel PORT (misal Render)
if [ -n "$PORT" ]; then
    sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
    sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf
fi

# Cache konfigurasi & route Laravel
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Jalankan migrasi database ke Supabase
php artisan migrate --force

# Jalankan Apache di foreground
exec apache2-foreground