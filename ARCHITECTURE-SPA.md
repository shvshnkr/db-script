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

### Implemented (phase 0–2 MVP)

| Method | Path | Auth |
|--------|------|------|
| POST | `/api/v1/auth/login` | no |
| POST | `/api/v1/auth/logout` | yes |
| GET | `/api/v1/auth/me` | yes |
| GET | `/api/v1/theme` | no |
| GET | `/api/v1/tables` | yes |
| GET | `/api/v1/tables/{id}/meta` | yes |
| GET | `/api/v1/tables/{id}/rows` | yes |
| GET | `/api/v1/tables/{id}/rows/{pk}` | yes |
| POST | `/api/v1/tables/{id}/rows` | yes |
| PUT | `/api/v1/tables/{id}/rows/{pk}` | yes |
| DELETE | `/api/v1/tables/{id}/rows` | yes |

Reader, files, menu — phase 3–5. See [`openapi.yaml`](openapi.yaml).

---

## Frontend

```
frontend/
  src/
    api/client.ts       fetch + credentials + X-Requested-With
    auth/AuthContext.tsx
    layout/AppShell.tsx
    pages/LoginPage.tsx, EditorPage.tsx
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

## Removed (after parity gates)

| Legacy | When |
|--------|------|
| `w.php`, `wx.php` | Editor SPA green |
| `r.php` | Reader SPA green |
| `filemgr.php` | Files SPA green |
| `GlobalBridge` | with w/r removal |
| frameset `index.php` | redirect → `/app` |

---

## Non-goals (v1)

- Mobile native app, offline PWA
- Full LIVEMOD inline editing
- Admin SPA (SSR admin v1 only)
