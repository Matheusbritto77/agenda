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

# Create storage symlink
php artisan storage:link --force

# Execute the main container command
exec "$@"
