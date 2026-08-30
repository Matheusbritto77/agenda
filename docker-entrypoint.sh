#!/bin/bash
set -e

# Generate application key if not set
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate --force
fi

# Clear config cache so container environment variables take effect
php artisan config:clear || true

# Detect DB configuration if DB_HOST is set
DB_DRIVER="${DB_CONNECTION:-sqlite}"
DB_HOST_VAL="${DB_HOST:-}"

if [ -n "$DB_HOST_VAL" ]; then
    if [ -z "${DB_PORT:-}" ]; then
        if [ "$DB_DRIVER" = "pgsql" ]; then
            export DB_PORT="5432"
        else
            export DB_PORT="3306"
        fi
    fi

    echo "Waiting for database (${DB_DRIVER}) at ${DB_HOST_VAL}:${DB_PORT}..."
    until php -r '
$host = getenv("DB_HOST");
$driver = getenv("DB_CONNECTION") ?: "sqlite";
$port = (int) (getenv("DB_PORT") ?: ($driver === "pgsql" ? 5432 : 3306));
$user = getenv("DB_USERNAME") ?: "root";
$password = getenv("DB_PASSWORD") ?: "";
$database = getenv("DB_DATABASE") ?: "";

switch ($driver) {
    case "pgsql":
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
        break;
    case "mysql":
    case "mariadb":
        $dsn = "mysql:host={$host};port={$port};dbname={$database}";
        break;
    default:
        $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";
        break;
}

try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection waiting: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
'; do
        sleep 2
    done
fi

# Regenerate package manifests at runtime, when the real environment is available.
php artisan package:discover --ansi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force || echo "Migration warning: check logs if migration failed."

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
    chown www-data:www-data database/database.sqlite 2>/dev/null || true
    chmod 664 database/database.sqlite 2>/dev/null || true
fi
chown -R www-data:www-data database 2>/dev/null || true

# Fix permissions on storage and bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Create storage symlink
php artisan storage:link --force 2>/dev/null || true

# Execute the main container command
exec "$@"

