#!/bin/bash
# Setup Dbscript4 dev environment in WSL (Docker Compose, isolated rollback via teardown).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MANIFEST="$ROOT/dev/install-manifest.txt"
COMPOSE_FILE="$ROOT/dev/docker-compose.yml"

log_install() {
    local pkg="$1"
    if ! grep -qxF "$pkg" "$MANIFEST" 2>/dev/null; then
        echo "$pkg" >> "$MANIFEST"
    fi
}

echo "=== Dbscript4 WSL setup ==="
echo "Project root: $ROOT"

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker not found. Install Docker Desktop + WSL integration, or:"
    echo "  sudo apt-get update && sudo apt-get install -y docker.io docker-compose-plugin"
    if command -v apt-get >/dev/null 2>&1; then
        read -r -p "Install docker.io via apt now? [y/N] " ans
        if [[ "${ans,,}" == "y" ]]; then
            sudo apt-get update
            sudo apt-get install -y docker.io docker-compose-plugin
            log_install "docker.io"
            log_install "docker-compose-plugin"
        else
            exit 1
        fi
    else
        exit 1
    fi
fi

if ! docker info >/dev/null 2>&1; then
    echo "Docker daemon not running. Start Docker Desktop or: sudo service docker start"
    exit 1
fi

mkdir -p "$ROOT/_conf" "$ROOT/_logs" "$ROOT/_local" "$ROOT/_data"
chmod -R 775 "$ROOT/_conf" "$ROOT/_logs" "$ROOT/_local" "$ROOT/_data" 2>/dev/null || true

echo "Building and starting containers..."
docker compose -f "$COMPOSE_FILE" up -d --build

echo ""
echo "=== Ready ==="
echo "  Install wizard: http://localhost:8080/install.php"
echo "  MySQL host (from web container): db"
echo "  MySQL root password: dbscript_root"
echo "  MySQL database: dbscript_test"
echo ""
echo "Rollback environment:"
echo "  bash scripts/teardown-wsl.sh"
echo ""
echo "Optional WSL snapshot before experiments (PowerShell on Windows):"
echo "  wsl --export Ubuntu C:\\Users\\user\\backups\\wsl-before-dbscript4.tar"
