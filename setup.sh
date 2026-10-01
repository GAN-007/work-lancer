#!/usr/bin/env bash
set -Eeuo pipefail

REPO_URL="${WORKLANCER_REPO_URL:-https://github.com/GAN-007/work-lancer.git}"
DEFAULT_DIR="${WORKLANCER_DIR:-${PWD}/work-lancer}"
REQUESTED_PORT=""
OPEN_BROWSER=1
ENABLE_BASEROW=0
PULL_REPO=1
SKIP_SYSTEM_INSTALL=0
AFTER_PULL=0
ORIGINAL_ARGS=("$@")
ROOT=""
ENV_FILE=""
COMPOSE_FILE=""
DOCKER=()
COMPOSE=()

log()  { printf '\033[1;34m[worklancer]\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m[ok]\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m[warn]\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31m[error]\033[0m %s\n' "$*" >&2; exit 1; }

usage() {
  cat <<'EOF'
Worklancer one-command setup

Usage:
  ./setup.sh [options]

Options:
  --dir PATH             Clone/use Worklancer at PATH when not already in the repo.
  --port PORT            Use a specific localhost port instead of auto-selecting one.
  --no-open              Do not open the browser after startup.
  --baserow              Start the optional Baserow service too.
  --no-pull              Do not update the existing Git checkout.
  --skip-system-install  Do not install missing host packages automatically.
  -h, --help             Show this help.

Environment overrides:
  WORKLANCER_DIR
  WORKLANCER_REPO_URL
  OLLAMA_MODEL
  WORKLANCER_PORT

The script installs/detects the host bootstrap tools, updates or clones Worklancer,
discovers dependency manifests, installs the Docker/Compose runtime when possible,
generates local secrets, builds all app/frontend/Python dependencies in containers,
runs migrations, starts the complete stack, selects a free port, health-checks HTTP,
and opens the resulting URL in your browser.
EOF
}

while (($#)); do
  case "$1" in
    --dir)
      [[ $# -ge 2 ]] || die "--dir requires a path"
      DEFAULT_DIR="$2"
      shift 2
      ;;
    --port)
      [[ $# -ge 2 ]] || die "--port requires a port number"
      REQUESTED_PORT="$2"
      shift 2
      ;;
    --no-open)
      OPEN_BROWSER=0
      shift
      ;;
    --baserow)
      ENABLE_BASEROW=1
      shift
      ;;
    --no-pull)
      PULL_REPO=0
      shift
      ;;
    --skip-system-install)
      SKIP_SYSTEM_INSTALL=1
      shift
      ;;
    --after-pull)
      AFTER_PULL=1
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      die "Unknown option: $1"
      ;;
  esac
done

as_root() {
  if [[ "$(id -u)" -eq 0 ]]; then
    "$@"
  elif command -v sudo >/dev/null 2>&1; then
    sudo "$@"
  else
    die "Root privileges are required for system package installation. Install sudo or rerun as root."
  fi
}

install_bootstrap_tools() {
  local missing=()
  for cmd in git curl openssl; do
    command -v "$cmd" >/dev/null 2>&1 || missing+=("$cmd")
  done
  (("${#missing[@]}" == 0)) && return 0
  ((SKIP_SYSTEM_INSTALL == 0)) || die "Missing bootstrap tools: ${missing[*]}"

  log "Installing bootstrap tools: ${missing[*]}"
  if command -v apt-get >/dev/null 2>&1; then
    as_root apt-get update
    as_root apt-get install -y git curl openssl ca-certificates
  elif command -v dnf >/dev/null 2>&1; then
    as_root dnf install -y git curl openssl ca-certificates
  elif command -v pacman >/dev/null 2>&1; then
    as_root pacman -Sy --needed --noconfirm git curl openssl ca-certificates
  elif command -v brew >/dev/null 2>&1; then
    brew install git curl openssl
  else
    die "Cannot install git/curl/openssl automatically on this OS. Install them and rerun setup.sh."
  fi
}

repo_root_from() {
  local path="$1"
  git -C "$path" rev-parse --show-toplevel 2>/dev/null || true
}

sync_repository() {
  local script_dir candidate
  script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" 2>/dev/null && pwd 2>/dev/null || pwd)"
  candidate="$(repo_root_from "$script_dir")"
  [[ -n "$candidate" ]] || candidate="$(repo_root_from "$PWD")"

  if [[ -z "$candidate" ]]; then
    ROOT="$(python3 - <<PY 2>/dev/null || true
import os
print(os.path.abspath(os.path.expanduser(r'''$DEFAULT_DIR''')))
PY
)"
    [[ -n "$ROOT" ]] || ROOT="$DEFAULT_DIR"
    if [[ -d "$ROOT/.git" ]]; then
      :
    elif [[ -e "$ROOT" && -n "$(ls -A "$ROOT" 2>/dev/null || true)" ]]; then
      die "Target directory exists and is not an empty Git checkout: $ROOT"
    else
      log "Cloning Worklancer into $ROOT"
      mkdir -p "$(dirname "$ROOT")"
      git clone "$REPO_URL" "$ROOT"
      if ((AFTER_PULL == 0)); then
        exec env WORKLANCER_SETUP_REEXEC=1 "$ROOT/setup.sh" --after-pull "${ORIGINAL_ARGS[@]}"
      fi
    fi
  else
    ROOT="$candidate"
  fi

  if ((PULL_REPO == 1 && AFTER_PULL == 0)); then
    local before after current
    before="$(sha256_file "$ROOT/setup.sh" 2>/dev/null || true)"
    current="$(git -C "$ROOT" branch --show-current 2>/dev/null || true)"
    log "Updating Git checkout${current:+ ($current)}"
    git -C "$ROOT" fetch --prune origin
    if ! git -C "$ROOT" pull --ff-only; then
      die "Git pull could not fast-forward safely. Commit/stash local changes or run with --no-pull."
    fi
    after="$(sha256_file "$ROOT/setup.sh" 2>/dev/null || true)"
    if [[ -n "$before" && -n "$after" && "$before" != "$after" && "${WORKLANCER_SETUP_REEXEC:-0}" != "1" ]]; then
      log "setup.sh was updated by git pull; restarting with the newest script"
      exec env WORKLANCER_SETUP_REEXEC=1 "$ROOT/setup.sh" --after-pull "${ORIGINAL_ARGS[@]}"
    fi
  fi

  cd "$ROOT"
  ENV_FILE="$ROOT/.env.open-source"
  COMPOSE_FILE="$ROOT/docker-compose.open-source.yml"
}

sha256_file() {
  local path="$1"
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$path" | awk '{print $1}'
  elif command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$path" | awk '{print $1}'
  else
    openssl dgst -sha256 "$path" | awk '{print $NF}'
  fi
}

install_runtime() {
  if ! command -v docker >/dev/null 2>&1; then
    ((SKIP_SYSTEM_INSTALL == 0)) || die "Docker is not installed."
    log "Docker is missing; installing the local container runtime"

    if command -v apt-get >/dev/null 2>&1; then
      as_root apt-get update
      as_root apt-get install -y docker.io iproute2
      as_root apt-get install -y docker-compose-v2 2>/dev/null \
        || as_root apt-get install -y docker-compose-plugin 2>/dev/null \
        || as_root apt-get install -y docker-compose
    elif command -v dnf >/dev/null 2>&1; then
      as_root dnf install -y docker docker-compose-plugin iproute
    elif command -v pacman >/dev/null 2>&1; then
      as_root pacman -Sy --needed --noconfirm docker docker-compose iproute2
    elif command -v brew >/dev/null 2>&1; then
      brew install docker docker-compose colima
      colima start
    else
      die "Docker could not be installed automatically on this OS."
    fi
  fi

  if [[ "$(uname -s)" == "Linux" ]]; then
    if ! docker info >/dev/null 2>&1; then
      if command -v systemctl >/dev/null 2>&1; then
        as_root systemctl enable --now docker >/dev/null 2>&1 || true
      elif command -v service >/dev/null 2>&1; then
        as_root service docker start >/dev/null 2>&1 || true
      fi
    fi
  elif [[ "$(uname -s)" == "Darwin" ]] && ! docker info >/dev/null 2>&1; then
    if command -v colima >/dev/null 2>&1; then
      colima start
    fi
  fi

  if docker info >/dev/null 2>&1; then
    DOCKER=(docker)
  elif command -v sudo >/dev/null 2>&1 && sudo docker info >/dev/null 2>&1; then
    DOCKER=(sudo docker)
  else
    die "Docker is installed but the daemon is not accessible. Start Docker and rerun setup.sh."
  fi

  if "${DOCKER[@]}" compose version >/dev/null 2>&1; then
    COMPOSE=("${DOCKER[@]}" compose)
  elif command -v docker-compose >/dev/null 2>&1; then
    if [[ "${DOCKER[0]}" == "sudo" ]]; then
      COMPOSE=(sudo docker-compose)
    else
      COMPOSE=(docker-compose)
    fi
  else
    ((SKIP_SYSTEM_INSTALL == 0)) || die "Docker Compose is missing."
    if command -v apt-get >/dev/null 2>&1; then
      as_root apt-get update
      as_root apt-get install -y docker-compose-v2 2>/dev/null \
        || as_root apt-get install -y docker-compose-plugin 2>/dev/null \
        || as_root apt-get install -y docker-compose
    elif command -v dnf >/dev/null 2>&1; then
      as_root dnf install -y docker-compose-plugin
    elif command -v pacman >/dev/null 2>&1; then
      as_root pacman -Sy --needed --noconfirm docker-compose
    elif command -v brew >/dev/null 2>&1; then
      brew install docker-compose
    fi

    if "${DOCKER[@]}" compose version >/dev/null 2>&1; then
      COMPOSE=("${DOCKER[@]}" compose)
    elif command -v docker-compose >/dev/null 2>&1; then
      COMPOSE=(docker-compose)
    else
      die "Docker Compose installation failed."
    fi
  fi

  ok "Docker: $("${DOCKER[@]}" --version)"
  ok "Compose: $("${COMPOSE[@]}" version --short 2>/dev/null || "${COMPOSE[@]}" version)"
}

discover_manifests() {
  log "Discovering application dependency manifests"
  local found=0 path
  while IFS= read -r path; do
    found=1
    printf '  - %s\n' "${path#"$ROOT"/}"
  done < <(find "$ROOT" -maxdepth 4 -type f \( \
      -name composer.json -o -name composer.lock -o \
      -name package.json -o -name package-lock.json -o \
      -name requirements.txt -o -name pyproject.toml -o \
      -name Dockerfile -o -name 'docker-compose*.yml' -o -name 'docker-compose*.yaml' \
    \) | sort)
  ((found == 1)) || die "No application dependency manifests were found."
}

is_port_free() {
  local port="$1"
  if command -v ss >/dev/null 2>&1; then
    ! ss -ltn 2>/dev/null | awk '{print $4}' | grep -Eq "[:.]$port$"
  elif command -v lsof >/dev/null 2>&1; then
    ! lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1
  elif command -v python3 >/dev/null 2>&1; then
    python3 - "$port" <<'PY'
import socket, sys
s=socket.socket()
try:
    s.bind(("127.0.0.1", int(sys.argv[1])))
except OSError:
    raise SystemExit(1)
finally:
    s.close()
PY
  else
    ! curl -fsS --max-time 1 "http://127.0.0.1:$port/" >/dev/null 2>&1
  fi
}

choose_port() {
  local port
  if [[ -n "$REQUESTED_PORT" ]]; then
    [[ "$REQUESTED_PORT" =~ ^[0-9]+$ ]] || die "--port must be numeric"
    ((REQUESTED_PORT >= 1024 && REQUESTED_PORT <= 65535)) || die "--port must be between 1024 and 65535"
    is_port_free "$REQUESTED_PORT" || die "Port $REQUESTED_PORT is already in use"
    printf '%s' "$REQUESTED_PORT"
    return
  fi

  if [[ -n "${WORKLANCER_PORT:-}" ]] && [[ "${WORKLANCER_PORT}" =~ ^[0-9]+$ ]] && is_port_free "${WORKLANCER_PORT}"; then
    printf '%s' "${WORKLANCER_PORT}"
    return
  fi

  for ((port=8088; port<=8188; port++)); do
    if is_port_free "$port"; then
      printf '%s' "$port"
      return
    fi
  done

  die "Could not find a free localhost port in 8088-8188"
}

env_get() {
  local key="$1"
  [[ -f "$ENV_FILE" ]] || return 1
  grep -E "^$key=" "$ENV_FILE" | tail -n1 | cut -d= -f2- || true
}

env_set() {
  local key="$1" value="$2" tmp
  touch "$ENV_FILE"
  if grep -qE "^$key=" "$ENV_FILE"; then
    tmp="$(mktemp)"
    awk -v key="$key" -v value="$value" 'BEGIN{FS="="} $1==key {print key "=" value; next} {print}' "$ENV_FILE" > "$tmp"
    mv "$tmp" "$ENV_FILE"
  else
    printf '%s=%s\n' "$key" "$value" >> "$ENV_FILE"
  fi
}

ensure_env() {
  local port="$1" baserow_port
  if [[ ! -f "$ENV_FILE" ]]; then
    umask 077
    touch "$ENV_FILE"
    log "Creating $ENV_FILE"
  fi

  [[ -n "$(env_get APP_KEY)" ]] || env_set APP_KEY "base64:$(openssl rand -base64 32 | tr -d '\n')"
  [[ -n "$(env_get POSTGRES_PASSWORD)" ]] || env_set POSTGRES_PASSWORD "$(openssl rand -hex 32)"
  [[ -n "$(env_get SEARXNG_SECRET)" ]] || env_set SEARXNG_SECRET "$(openssl rand -hex 32)"
  [[ -n "$(env_get OPEN_WEB_AGENT_TOKEN)" ]] || env_set OPEN_WEB_AGENT_TOKEN "$(openssl rand -hex 32)"
  [[ -n "$(env_get GALIKA_HEALTH_TOKEN)" ]] || env_set GALIKA_HEALTH_TOKEN "$(openssl rand -hex 32)"
  [[ -n "$(env_get OLLAMA_MODEL)" ]] || env_set OLLAMA_MODEL "${OLLAMA_MODEL:-qwen3:8b}"
  [[ -n "$(env_get LOCAL_LLM_PROVIDER)" ]] || env_set LOCAL_LLM_PROVIDER "ollama"
  [[ -n "$(env_get BASEROW_ENABLED)" ]] || env_set BASEROW_ENABLED "false"
  env_set WORKLANCER_PORT "$port"

  if ((ENABLE_BASEROW == 1)); then
    env_set BASEROW_ENABLED "true"
    baserow_port="$(env_get BASEROW_PORT || true)"
    if [[ -z "$baserow_port" || ! "$baserow_port" =~ ^[0-9]+$ || "$baserow_port" == "$port" ]] || ! is_port_free "$baserow_port"; then
      for ((baserow_port=8090; baserow_port<=8190; baserow_port++)); do
        [[ "$baserow_port" == "$port" ]] && continue
        if is_port_free "$baserow_port"; then
          break
        fi
      done
      ((baserow_port <= 8190)) || die "Could not find a free port for optional Baserow"
    fi
    env_set BASEROW_PORT "$baserow_port"
  fi

  chmod 600 "$ENV_FILE" 2>/dev/null || true
  ok "Local environment is configured at .env.open-source"
}

compose_cmd() {
  "${COMPOSE[@]}" --env-file "$ENV_FILE" -f "$COMPOSE_FILE" "$@"
}

failure_diagnostics() {
  local code=$?
  trap - ERR
  warn "Setup stopped with exit code $code."
  if [[ -n "$ROOT" && -f "${COMPOSE_FILE:-}" && "${#COMPOSE[@]}" -gt 0 && -f "${ENV_FILE:-}" ]]; then
    compose_cmd ps || true
    compose_cmd logs --tail=80 app open-web-agent postgres redis ollama searxng 2>/dev/null || true
  fi
  exit "$code"
}

wait_for_http() {
  local url="$1" attempts=90
  log "Waiting for Worklancer HTTP readiness at $url"
  for ((i=1; i<=attempts; i++)); do
    if curl -fsS --max-time 3 "$url" >/dev/null 2>&1; then
      ok "Worklancer is responding over HTTP"
      return 0
    fi
    sleep 2
  done
  return 1
}

open_browser() {
  local url="$1"
  ((OPEN_BROWSER == 1)) || return 0

  if command -v xdg-open >/dev/null 2>&1; then
    nohup xdg-open "$url" >/dev/null 2>&1 &
  elif [[ "$(uname -s)" == "Darwin" ]] && command -v open >/dev/null 2>&1; then
    open "$url" >/dev/null 2>&1 &
  elif grep -qi microsoft /proc/version 2>/dev/null && command -v cmd.exe >/dev/null 2>&1; then
    cmd.exe /C start "" "$url" >/dev/null 2>&1 &
  elif command -v sensible-browser >/dev/null 2>&1; then
    nohup sensible-browser "$url" >/dev/null 2>&1 &
  else
    warn "No browser opener was detected. Open this URL manually: $url"
    return 0
  fi
  ok "Opened $url in the default browser"
}

main() {
  install_bootstrap_tools
  sync_repository
  trap failure_diagnostics ERR

  [[ -f "$COMPOSE_FILE" ]] || die "Missing docker-compose.open-source.yml in $ROOT"

  install_runtime
  discover_manifests

  local port url
  port="$(choose_port)"
  url="http://127.0.0.1:$port"

  if [[ -f "$ENV_FILE" ]] && [[ "$(env_get BASEROW_ENABLED || true)" == "true" ]]; then
    ENABLE_BASEROW=1
  fi

  ensure_env "$port"

  log "Selected free localhost port: $port"
  log "Building and starting the complete Worklancer stack"
  if ((ENABLE_BASEROW == 1)); then
    compose_cmd --profile baserow up -d --build --remove-orphans
  else
    compose_cmd up -d --build --remove-orphans
  fi

  log "Running database migrations"
  compose_cmd exec -T app php artisan migrate --force

  log "Clearing stale Laravel caches and ensuring storage link"
  compose_cmd exec -T app php artisan optimize:clear
  compose_cmd exec -T app php artisan storage:link >/dev/null 2>&1 || true

  wait_for_http "$url"

  echo
  compose_cmd ps
  echo
  ok "Worklancer is running end to end."
  printf 'URL: %s\n' "$url"
  if ((ENABLE_BASEROW == 1)); then
    printf 'Baserow: http://127.0.0.1:%s\n' "$(env_get BASEROW_PORT)"
  fi
  printf 'Stop: %q ' "${COMPOSE[@]}"
  printf '%q ' --env-file "$ENV_FILE" -f "$COMPOSE_FILE" down
  printf '\n'

  open_browser "$url"
}

main
