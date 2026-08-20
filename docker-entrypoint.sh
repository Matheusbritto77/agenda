#!/bin/bash
set -e

# Generate application key if not set
if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

# Run database migrations
php artisan migrate --force

# Create storage symlink
php artisan storage:link --force

# Execute the main container command
exec "$@"
