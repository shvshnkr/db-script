# Dbscript 4 — arch-spa

Ветка **`arch-spa`** от **`arch-modern`**: REST API v1 + React SPA (`/app/*`).

## Что это

| | arch-modern | arch-spa |
|---|-------------|----------|
| UI | Twig SSR | React 19 + Vite + TypeScript |
| Editor | w-arch.php | `/app/editor/:tableId` |
| API | контракт в ARCHITECTURE.md | `/api/v1/*` |

Dbscript на этом форке **не в prod** — здесь собран прототип редактора на React SPA для ознакомления автором, без legacy-миграций.

## Документы

- [`ARCHITECTURE-SPA.md`](ARCHITECTURE-SPA.md) — API + SPA слой
- [`DESIGN-SPA.md`](DESIGN-SPA.md) — UX/UI токены и чеклист
- [`openapi.yaml`](openapi.yaml) — REST контракт
- Handoff: [`_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md`](_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md)

## Dev

```bash
composer install
docker compose -f dev/docker-compose.yml up -d --build
docker compose exec web php scripts/arch-modern-install-dev.php testpass12
docker compose exec web php scripts/arch-modern-seed-demo.php

cd frontend && npm ci && npm run dev
# SPA: http://127.0.0.1:5173/app/
# API proxied to http://127.0.0.1:8080/api/v1/
```

Prod build:

```bash
cd frontend && npm ci && npm run build
# → public/app/
```

Smoke:

```bash
docker compose exec web bash scripts/smoke-api-auth.sh http://127.0.0.1
vendor/bin/phpunit --testsuite unit
```

## Требования

- PHP 8.2+ (как arch-modern)
- Node 20+ для frontend
- MySQL (Docker compose)
