# Dbscript 4 — handoff ветки `arch-spa`

**Дата:** 2026-06 · **Аудитория:** dj--alex  
**Предшественник:** [`arch-modern-2026/handoff-djalex.ru.md`](../arch-modern-2026/handoff-djalex.ru.md)

---

## 1. Зачем ветка

`arch-spa` — **наследник `arch-modern`**: тот же backend (PSR-4, TOML, JWT, EditorService), но **UI — React SPA** вместо монолита `w.php` + frameset.

Цель: рабочий прототип редактора с привычным UX Dbscript на REST + React — для ознакомления автором, без prod-миграций.

---

## 2. Цепочка веток

```
php8-port → modern-ops → arch-modern → arch-spa
```

| Ветка | UI | Backend |
|-------|-----|---------|
| arch-modern | Twig SSR | Services API-ready |
| **arch-spa** | React `/app/*` | REST `/api/v1/*` |

Карта всех веток (канон на `arch-modern`): [BRANCH-MAP.ru.md](https://github.com/shvshnkr/db-script/blob/arch-modern/_langdb/.archive/branch-map-2026/BRANCH-MAP.ru.md)

---

## 3. Что взято из arch-modern

| Компонент | Путь |
|-----------|------|
| Application, TOML | `src/Dbscript/Application.php`, `_conf/*.toml` |
| JWT cookie `dbs_jwt` | `JwtAuthService` |
| Editor CRUD | `EditorService` |
| Theme tokens | `ThemeService` → `GET /api/v1/theme` |
| Install / Admin SSR | `install-arch.php`, `admin-arch.php` |

---

## 4. API v1

Контракт: [`openapi.yaml`](../../../openapi.yaml)

Примеры:

```bash
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/api/v1/auth/me   # 401

curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin","password":"testpass12"}'

curl -s -H "Authorization: Bearer TOKEN" http://127.0.0.1/api/v1/tables
```

JSON envelope: `{ "data", "meta", "errors" }`.

---

## 5. Frontend

```
frontend/
  npm run dev    → Vite :5173, proxy /api
  npm run build  → public/app/
```

| Route | Страница |
|-------|----------|
| `/app/login` | LoginPage |
| `/app/editor/:tableId` | Editor CRUD, LIVEMOD, SQL, CSV |
| `/app/reader/:tableId` | Search, view, export |
| `/app/files` | File manager |
| `/app/converter` | A_IMPEXP fdb↔mysql |
| `/app/info/:slug` | .ver / .info / .author / .help |

---

## 6. UX / DESIGN-SPA

Документ: [`DESIGN-SPA.md`](../../../DESIGN-SPA.md)

- Toolbar: KEY_ADD/EDIT/DEL, LIVEMOD, KEY_EXECUTE, A_IMPEXP
- Tokens из `styles.toml` (`--color-accent` и др.)

**Статус v1:** полный editor + reader + files + converter; LIVEMOD inline; legacy entry points удалены.

---

## 7. Паритет w.php

| w.php | API | SPA | Статус |
|-------|-----|-----|--------|
| tbl picker | GET /tables | EditorPage select | ✅ |
| list rows | GET /rows | DataGrid | ✅ |
| KEY_ADD | POST /rows | RecordForm | ✅ |
| KEY_EDIT | PUT /rows/{pk} | Modal form | ✅ |
| KEY_DEL | DELETE /rows | bulk select | ✅ |
| KEY_EXECUTE | POST /sql | SqlPanel | ✅ |
| A_IMPEXP | POST /converter/*, import | ConverterPage + CSV | ✅ |
| LIVEMOD | PUT /rows/{pk} (field) | inline grid edit | ✅ (реализовано; в legacy был stub) |
| r.php .ver/.info | GET /info/{slug} | InfoPage | ✅ |
| filemgr.php | GET/POST /files | FilesPage | ✅ |

**Удалено (cutover):** `w.php`, `wx.php`, `r.php`, `filemgr.php`, `GlobalBridge`, frameset `index.php`.

---

## 8. Auth

- Cookie `dbs_jwt` (httpOnly, SameSite=Lax) для same-origin SPA
- `Authorization: Bearer` для API/smoke
- Header `X-Requested-With: DbscriptSPA` на mutating fetch

---

## 9. Как проверить

```bash
docker compose -f dev/docker-compose.yml up -d --build
docker compose exec web php scripts/arch-modern-install-dev.php testpass12
docker compose exec web php scripts/arch-modern-seed-demo.php
cd frontend && npm ci && npm run build
docker compose exec web bash scripts/smoke-all-spa.sh http://127.0.0.1
```

Gate для arch-spa: **`scripts/smoke-all-spa.sh`** (не `smoke-all.sh` из php8-port — тот ссылается на удалённые `w.php`/`wx.php`).

---

## 10. Non-goals

- Mobile app, offline PWA
- Admin SPA (остаётся SSR)
- SCP→CSV режим из legacy w.php (недоделан в оригинале)

---

## 11. Лицензия

Неофициальный форк для предложения архитектуры. Модель лицензии dj--alex не меняется.
