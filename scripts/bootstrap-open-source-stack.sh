#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT}/.env.open-source"

if [[ ! -f "${ENV_FILE}" ]]; then
  APP_KEY="base64:$(openssl rand -base64 32)"
  POSTGRES_PASSWORD="$(openssl rand -hex 32)"
  SEARXNG_SECRET="$(openssl rand -hex 32)"
  OPEN_WEB_AGENT_TOKEN="$(openssl rand -hex 32)"

  cat > "${ENV_FILE}" <<EOF
APP_KEY=${APP_KEY}
POSTGRES_PASSWORD=${POSTGRES_PASSWORD}
SEARXNG_SECRET=${SEARXNG_SECRET}
OPEN_WEB_AGENT_TOKEN=${OPEN_WEB_AGENT_TOKEN}
OLLAMA_MODEL=qwen3:8b
LOCAL_LLM_PROVIDER=ollama
BASEROW_ENABLED=false
EOF

  chmod 600 "${ENV_FILE}"
  echo "Created ${ENV_FILE} with generated local secrets."
else
  echo "Using existing ${ENV_FILE}."
fi

docker compose --env-file "${ENV_FILE}" -f "${ROOT}/docker-compose.open-source.yml" up -d --build
docker compose --env-file "${ENV_FILE}" -f "${ROOT}/docker-compose.open-source.yml" ps

echo
echo "Worklancer open-source stack started."
echo "Laravel: http://127.0.0.1:8088"
echo "To enable the optional Baserow UI:"
echo "  docker compose --env-file ${ENV_FILE} -f docker-compose.open-source.yml --profile baserow up -d baserow"
