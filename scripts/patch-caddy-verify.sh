#!/bin/bash
set -euo pipefail
CADDYFILE=/home/azureuser/pipeline-deployment/Automated-data-collection-and-validation-pipeline-/deploy/Caddyfile
python3 <<'PY'
from pathlib import Path
path = Path("/home/azureuser/pipeline-deployment/Automated-data-collection-and-validation-pipeline-/deploy/Caddyfile")
text = path.read_text()
old = """{$PIPELINE_DOMAIN} {
\t@verify path /verify/*
\thandle @verify {
\t\tredir https://ardhilens.dickens-manyama.tech{uri} permanent
\t}
\tencode gzip
\tbasicauth {
\t\t{$CADDY_BASIC_AUTH_USER} {$CADDY_BASIC_AUTH_HASH}
\t}
\treverse_proxy streamlit:8501
}"""
new = """{$PIPELINE_DOMAIN} {
\thandle /verify/* {
\t\tredir https://ardhilens.dickens-manyama.tech{uri} permanent
\t}
\thandle {
\t\tencode gzip
\t\tbasicauth {
\t\t\t{$CADDY_BASIC_AUTH_USER} {$CADDY_BASIC_AUTH_HASH}
\t\t}
\t\treverse_proxy streamlit:8501
\t}
}"""
if "handle /verify/*" in text and "handle {" in text.split("{$PIPELINE_DOMAIN}")[1].split("}")[0]:
    print("already patched")
elif old in text:
    path.write_text(text.replace(old, new))
    print("patched")
else:
    raise SystemExit("unexpected Caddyfile layout")
PY
cd /home/azureuser/pipeline-deployment/Automated-data-collection-and-validation-pipeline-
docker compose -f docker-compose.yml -f docker-compose.prod.yml restart caddy
