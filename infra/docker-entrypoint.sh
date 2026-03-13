#!/bin/sh
set -e

# Install/update composer dependencies if vendor is missing or outdated
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "[entrypoint] Installing composer dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[entrypoint] Generating app key..."
    php artisan key:generate --force
fi

# Run migrations
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction

# Create storage symlink if needed
php artisan storage:link --force 2>/dev/null || true

echo "[entrypoint] Starting: $@"
exec "$@"
