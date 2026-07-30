#!/bin/bash
set -euo pipefail

cd /home/azureuser/ardhilens-deployment/ArdhiLens-backend

echo "Pulling latest from GitHub..."
git pull origin main

if [ ! -f .env ]; then
  cp .env.docker.example .env
  DB_PASS=$(openssl rand -hex 16)
  DB_ROOT=$(openssl rand -hex 16)
  sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
  sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=${DB_ROOT}|" .env
  echo ".env created"
fi

docker compose -f docker-compose.yml -f docker-compose.prod.yml build app
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

echo "Waiting for services..."
sleep 20

docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan migrate --force

docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan db:seed --class=LandDemoSeeder --force || true

bash "$(dirname "$0")/fix-caddy.sh"

docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
echo "Done: https://ardhilens.dickens-manyama.tech"
