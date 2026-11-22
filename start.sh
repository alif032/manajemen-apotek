#!/usr/bin/env bash
set -e

TARGET_PORT="${PORT:-80}"

echo "Mengonfigurasi Apache agar listen pada port ${TARGET_PORT}..."
sed -ri "s/^\s*Listen\s+[0-9]+/Listen ${TARGET_PORT}/g" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${TARGET_PORT}>/g" /etc/apache2/sites-available/*.conf

# Pastikan folder storage & cache bisa ditulis oleh www-data
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Bersihkan lalu bangun ulang cache Laravel untuk produksi
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "Menjalankan migrasi database..."
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Menjalankan Apache pada port ${TARGET_PORT}..."
exec apache2-foreground