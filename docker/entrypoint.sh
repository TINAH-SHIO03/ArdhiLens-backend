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

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not set. Add APP_KEY to the host .env file before starting."
    exit 1
fi

php artisan storage:link --force 2>/dev/null || true
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
