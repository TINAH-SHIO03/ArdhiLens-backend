#!/bin/bash
set -e
cd /home/azureuser/ardhilens-deployment/ArdhiLens-backend

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  KEY=$(docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));")
  sed -i "s|^APP_KEY=.*|APP_KEY=${KEY}|" .env
  echo "APP_KEY generated"
fi

docker compose -f docker-compose.yml -f docker-compose.prod.yml build app
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
sleep 30
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs app --tail 20
