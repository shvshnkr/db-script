# Dbscript 4 — arch-spa

Ветка **`arch-spa`** от **`arch-modern`**: REST API v1 + React SPA (`/app/*`).

## Что это

| | arch-modern | arch-spa |
|---|-------------|----------|
| UI | Twig SSR | React 19 + Vite + TypeScript |
| Editor | w-arch.php | `/app/editor/:tableId` |
| API | контракт в ARCHITECTURE.md | `/api/v1/*` |

Dbscript на этом форке **не в prod** — прототип редактора на React SPA для ознакомления автором.

## Документы

- [`ARCHITECTURE-SPA.md`](ARCHITECTURE-SPA.md) — API + SPA слой, паритет w.php
- [`DESIGN-SPA.md`](DESIGN-SPA.md) — UX/UI токены
- [`openapi.yaml`](openapi.yaml) — REST контракт
- Handoff: [`_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md`](_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md)
- Карта веток: [`_langdb/.archive/branch-map-2026/BRANCH-MAP.ru.md`](_langdb/.archive/branch-map-2026/BRANCH-MAP.ru.md)

## Dev

```bash
composer install
docker compose -f dev/docker-compose.yml up -d --build
docker compose exec web php scripts/arch-modern-install-dev.php testpass12
docker compose exec web php scripts/arch-modern-seed-demo.php

cd frontend && npm ci && npm run dev
# SPA: http://127.0.0.1:5173/app/
```

Prod build:

```bash
cd frontend && npm ci && npm run build
# → public/app/
```

## Smoke (arch-spa gate)

```bash
docker compose exec web bash scripts/smoke-all-spa.sh http://127.0.0.1
```

Включает: PHPUnit, SPA build check, auth, editor CRUD, LIVEMOD PUT, CSV import, reader, files, converter fdb↔mysql, index-router, auth gates.

> Для `php8-port` используйте `scripts/smoke-all.sh`. Для **arch-spa** — только `smoke-all-spa.sh`.

## Требования

- PHP 8.2+ (как arch-modern)
- Node 20+ для frontend
- MySQL (Docker compose)
