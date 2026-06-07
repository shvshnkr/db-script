# Dbscript 4 — Architecture (arch-spa)

**Branch:** `arch-spa` · **Base:** `arch-modern`  
**Audience:** dj--alex — SPA + REST layer on top of arch-modern services.

---

## Chain

```
php8-port → modern-ops → arch-modern → arch-spa
```

| Branch | UI | Backend |
|--------|-----|---------|
| arch-modern | Twig SSR | Services (API-ready contract) |
| **arch-spa** | React `/app/*` | REST `/api/v1/*` |

---

## HTTP routing

| Path | Handler |
|------|---------|
| `/api/v1/*` | [`api/index.php`](api/index.php) → `ApiKernel` |
| `/app/*` | Static [`public/app/`](public/app/) (Vite build) |
| `/install-arch.php` | SSR install (unchanged) |
| `/admin-arch.php` | SSR admin v1 |

Apache: [`.htaccess`](.htaccess) rewrites `/api/v1/` → `api/index.php`, SPA fallback for `/app/`.

Dev: `npm run dev` in `frontend/` proxies `/api` → PHP (port 8080).

---

## API layer

```
api/index.php
  → Application::boot()
  → ApiKernel
      → ApiRouter (method + path)
      → JwtAuthService (cookie dbs_jwt + Bearer)
      → *ApiController → *Service
```

JSON envelope:

```json
{ "data": {}, "meta": {}, "errors": null }
```

Errors: `{ "code": "notrights", "message": "..." }` with HTTP 4xx.

### Implemented (phase 0–6)

| Method | Path | Auth |
|--------|------|------|
| POST | `/api/v1/auth/login` | no |
| POST | `/api/v1/auth/logout` | yes |
| GET | `/api/v1/auth/me` | yes |
| GET | `/api/v1/theme` | no |
| GET | `/api/v1/menu` | yes |
| GET | `/api/v1/i18n` | no |
| GET | `/api/v1/i18n/languages` | no |
| GET/POST/PUT/DELETE | `/api/v1/tables/...` | yes |
| POST | `/api/v1/tables/{id}/import` | yes |
| GET | `/api/v1/reader/tables/{id}/search` | yes |
| GET | `/api/v1/reader/tables/{id}/rows/{pk}` | yes |
| GET | `/api/v1/reader/tables/{id}/export.csv` | yes |
| GET/POST/DELETE | `/api/v1/files/...` | yes |
| POST | `/api/v1/sql/execute` | yes |

See [`openapi.yaml`](openapi.yaml).

---

## Frontend

```
frontend/
  src/
    api/client.ts       fetch + credentials + X-Requested-With
    auth/AuthContext.tsx
    layout/AppShell.tsx
    pages/LoginPage.tsx, EditorPage.tsx, ReaderPage.tsx, FilesPage.tsx
    components/ui/      Button, Input, Modal, Toast
    styles/tokens.css
```

Build: `cd frontend && npm ci && npm run build` → `public/app/`.

---

## Auth model

- **Same-origin SPA:** httpOnly cookie `dbs_jwt` set on login (SameSite=Lax).
- **API clients / smoke:** `Authorization: Bearer <token>`.
- Mutating requests include `X-Requested-With: DbscriptSPA` (CSRF hint for future double-submit).

Inherited from arch-modern: `JwtAuthService`, `UserRepository`, TOML users.

---

## Verification

```bash
composer install
vendor/bin/phpunit --testsuite unit
cd frontend && npm ci && npm run build
docker compose -f dev/docker-compose.yml up -d --build
docker compose exec web bash scripts/smoke-api-auth.sh http://127.0.0.1
```

CI: [`.github/workflows/arch-spa-ci.yml`](.github/workflows/arch-spa-ci.yml)

Handoff (RU): [`_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md`](_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md)

---

## Removed (arch-spa cutover)

| Legacy | Replacement |
|--------|-------------|
| `w.php`, `wx.php` | `/app/editor` |
| `r.php` | `/app/reader` |
| `filemgr.php` | `/app/files` |
| `GlobalBridge` | removed (unused) |
| frameset `index.php` | redirect → `/app` |

---

## Non-goals (v1)

- Mobile native app, offline PWA
- Full LIVEMOD inline editing
- Admin SPA (SSR admin v1 only)
