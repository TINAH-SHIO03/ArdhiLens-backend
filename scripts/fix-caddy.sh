#!/bin/bash
set -euo pipefail

CADDYFILE=/home/azureuser/pipeline-deployment/Automated-data-collection-and-validation-pipeline-/deploy/Caddyfile
PIPELINE_DIR=/home/azureuser/pipeline-deployment/Automated-data-collection-and-validation-pipeline-
ARDHI_ENV=/home/azureuser/ardhilens-deployment/ArdhiLens-backend/.env

# Remove mistaken pipeline -> ardhilens-app blocks from early deploy scripts
sed -i '/^pipeline\.dickens-manyama\.tech {/,/^}$/d' "$CADDYFILE"

if ! grep -q 'ardhilens.dickens-manyama.tech' "$CADDYFILE"; then
  cat >> "$CADDYFILE" <<'EOF'

ardhilens.dickens-manyama.tech {
	encode gzip
	reverse_proxy ardhilens-app:80
}
EOF
fi

# Old certificates may QR-link to pipeline domain — redirect /verify/* to ArdhiLens
if ! grep -q 'handle /verify/\*' "$CADDYFILE"; then
  bash "$(dirname "$0")/patch-caddy-verify.sh"
fi

cd "$PIPELINE_DIR"
docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T caddy caddy reload --config /etc/caddy/Caddyfile 2>/dev/null || \
  docker compose -f docker-compose.yml -f docker-compose.prod.yml restart caddy
sleep 3

if [ -f "$ARDHI_ENV" ]; then
  cd /home/azureuser/ardhilens-deployment/ArdhiLens-backend
  sed -i 's|^APP_URL=.*|APP_URL=https://ardhilens.dickens-manyama.tech|' "$ARDHI_ENV"
  if grep -q '^CERTIFICATE_VERIFICATION_DOMAIN=' "$ARDHI_ENV"; then
    sed -i 's|^CERTIFICATE_VERIFICATION_DOMAIN=.*|CERTIFICATE_VERIFICATION_DOMAIN=https://ardhilens.dickens-manyama.tech/verify|' "$ARDHI_ENV"
  else
    echo 'CERTIFICATE_VERIFICATION_DOMAIN=https://ardhilens.dickens-manyama.tech/verify' >> "$ARDHI_ENV"
  fi
  docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --force-recreate app
  docker compose -f docker-compose.yml -f docker-compose.prod.yml exec -T app php artisan config:clear
fi

echo "Caddy + cert verify URL fixed. Scan: https://ardhilens.dickens-manyama.tech/verify/{certificate-number}"
