#!/bin/sh
set -e

cd /var/www/html

echo "Waiting for MySQL..."
until php -r '
    try {
        $host = getenv("DB_HOST") ?: "mysql";
        $port = getenv("DB_PORT") ?: "3306";
        $user = getenv("DB_USERNAME") ?: "ardhilens";
        $pass = getenv("DB_PASSWORD") ?: "";
        new PDO("mysql:host={$host};port={$port}", $user, $pass);
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    sleep 2
done
echo "MySQL is ready."

if [ ! -f .env ]; then
    echo "ERROR: .env file missing. Copy .env.docker.example to .env before starting."
    exit 1
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan storage:link --force 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
