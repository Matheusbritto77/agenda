#!/bin/bash
set -e

# Generate application key if not set
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

# Regenerate package manifests at runtime, when the real environment is available.
php artisan package:discover --ansi

# Run database migrations
php artisan migrate --force

# Ensure storage and cache directories exist with correct permissions inside the volume mount
mkdir -p storage/app/public/branding/banners
mkdir -p storage/app/public/avatars
mkdir -p storage/app/public/services
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# Fix sqlite database permission if exists
if [ -f database/database.sqlite ]; then
    chown www-data:www-data database/database.sqlite
    chmod 664 database/database.sqlite
fi
chown -R www-data:www-data database

# Fix permissions on storage and bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Create storage symlink
php artisan storage:link --force

# Execute the main container command
exec "$@"
