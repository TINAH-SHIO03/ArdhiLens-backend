#!/bin/bash
set -euo pipefail

cd /home/azureuser/ardhilens-deployment/ArdhiLens-backend

if ! grep -qE '^APP_KEY=base64:.+' .env 2>/dev/null; then
  KEY="$(docker run --rm php:8.4-cli php -r 'echo "base64:" . base64_encode(random_bytes(32));')"
  sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env
  echo "APP_KEY configured"
fi

docker compose -f docker-compose.yml -f docker-compose.prod.yml build app
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
sleep 40

docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan db:seed --class=LandDemoSeeder --force || true

bash "$(dirname "$0")/fix-caddy.sh"

docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo "Live at https://ardhilens.dickens-manyama.tech"
