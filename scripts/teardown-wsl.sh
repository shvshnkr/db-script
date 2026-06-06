#!/bin/bash
# Tear down Dbscript4 dev environment and optionally remove apt packages from manifest.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MANIFEST="$ROOT/dev/install-manifest.txt"
COMPOSE_FILE="$ROOT/dev/docker-compose.yml"

echo "=== Dbscript4 WSL teardown ==="

if command -v docker >/dev/null 2>&1; then
    docker compose -f "$COMPOSE_FILE" down -v --rmi local 2>/dev/null || true
    echo "Docker Compose stack removed (containers, volumes, local images)."
else
    echo "Docker not installed — skipping compose teardown."
fi

if [[ -f "$MANIFEST" ]] && [[ -s "$MANIFEST" ]] && command -v apt-get >/dev/null 2>&1; then
    read -r -p "Remove apt packages listed in dev/install-manifest.txt? [y/N] " ans
    if [[ "${ans,,}" == "y" ]]; then
        mapfile -t packages < "$MANIFEST"
        if ((${#packages[@]})); then
            sudo apt-get remove -y "${packages[@]}" || true
            sudo apt-get autoremove -y || true
        fi
        : > "$MANIFEST"
        echo "Manifest cleared."
    fi
fi

echo ""
echo "Fallback WSL restore (if you created a snapshot):"
echo "  wsl --terminate Ubuntu"
echo "  wsl --unregister Ubuntu"
echo "  wsl --import Ubuntu C:\\WSL\\Ubuntu C:\\Users\\user\\backups\\wsl-before-dbscript4.tar"
