#!/bin/sh
set -e

# Generate .env from Docker environment variables to prevent .env file from
# overriding container env vars (phpdotenv writes to $_ENV which takes precedence)
echo "[entrypoint] Writing .env from container environment..."
cat > .env <<EOF
APP_NAME=${APP_NAME:-DocumentScrapper}
APP_ENV=${APP_ENV:-local}
APP_KEY=${APP_KEY:-}
APP_DEBUG=${APP_DEBUG:-true}
APP_URL=${APP_URL:-http://localhost:8000}

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_LEVEL=${LOG_LEVEL:-debug}

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST:-postgres}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-documentscapper}
DB_USERNAME=${DB_USERNAME:-documentscapper}
DB_PASSWORD=${DB_PASSWORD:-secret}

BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-log}
FILESYSTEM_DISK=${FILESYSTEM_DISK:-local}
DOCUMENT_STORAGE_DISK=${DOCUMENT_STORAGE_DISK:-local}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-redis}

SESSION_DRIVER=${SESSION_DRIVER:-cookie}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
SESSION_DOMAIN=${SESSION_DOMAIN:-localhost}

CACHE_STORE=${CACHE_STORE:-redis}
CACHE_PREFIX=${CACHE_PREFIX:-documentscapper}

REDIS_CLIENT=${REDIS_CLIENT:-predis}
REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PORT=${REDIS_PORT:-6379}

OPENAI_API_KEY=${OPENAI_API_KEY:-}
OPENAI_MODEL_EXTRACTION=${OPENAI_MODEL_EXTRACTION:-gpt-4o-mini}
OPENAI_MODEL_CHAT=${OPENAI_MODEL_CHAT:-gpt-4o-mini}

SANCTUM_STATEFUL_DOMAINS=${SANCTUM_STATEFUL_DOMAINS:-localhost:5173,localhost:3000}
FRONTEND_URL=${FRONTEND_URL:-http://localhost:5173}
EOF

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
