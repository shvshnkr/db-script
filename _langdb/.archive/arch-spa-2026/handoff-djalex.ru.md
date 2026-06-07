# Dbscript 4 — handoff ветки `arch-spa`

**Дата:** 2026-06 · **Аудитория:** dj--alex  
**Предшественник:** [`arch-modern-2026/handoff-djalex.ru.md`](../arch-modern-2026/handoff-djalex.ru.md)

---

## 1. Зачем ветка

`arch-spa` — **наследник `arch-modern`**: тот же backend (PSR-4, TOML, JWT, EditorService), но **UI редактора — React SPA** вместо монолита `w.php` + frameset.

Цель: рабочий прототип редактора с привычным UX Dbscript, но на React SPA и REST API — для ознакомления автором, без prod-миграций.

---

## 2. Цепочка веток

```
php8-port → modern-ops → arch-modern → arch-spa
```

| Ветка | UI | Backend |
|-------|-----|---------|
| arch-modern | Twig SSR | Services API-ready |
| **arch-spa** | React `/app/*` | REST `/api/v1/*` |

---

## 3. Что взято из arch-modern

| Компонент | Путь |
|-----------|------|
| Application, TOML | `src/Dbscript/Application.php`, `_conf/*.toml` |
| JWT cookie `dbs_jwt` | `JwtAuthService` |
| Editor CRUD | `EditorService` |
| Theme tokens | `ThemeService` → `GET /api/v1/theme` |
| Install / Admin SSR | `install-arch.php`, `admin-arch.php` (v1) |

---

## 4. API v1

Контракт: [`openapi.yaml`](../../../openapi.yaml)

Примеры:

```bash
# 401 без auth
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/api/v1/auth/me

# login
curl -s -X POST http://127.0.0.1/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"login":"admin","password":"testpass12"}'

# tables (Bearer)
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

Роуты:

| Route | Страница |
|-------|----------|
| `/app/login` | LoginPage |
| `/app/editor/:tableId` | EditorPage |
| `/app/files` | FileManager (phase 3) |

---

## 6. UX / DESIGN-SPA

Документ: [`DESIGN-SPA.md`](../../../DESIGN-SPA.md)

- Toolbar mapping: KEY_ADD/EDIT/DEL → кнопки Editor
- Tokens из `styles.toml` (`--color-accent` и др.)
- Чеклист приёмки перед merge UI-фаз

**Статус v1:** Login + AppShell + Editor grid wireframe; CRUD modal — phase 2.

---

## 7. Паритет w.php

| w.php | API | SPA | Статус |
|-------|-----|-----|--------|
| tbl picker | GET /tables | EditorPage select | MVP |
| list rows | GET /rows | DataGrid | MVP |
| KEY_ADD | POST /rows | RecordForm | phase 2 |
| KEY_EDIT | PUT /rows/{pk} | Modal form | phase 2 |
| KEY_DEL | DELETE /rows | bulk select | phase 2 |
| KEY_EXECUTE | POST /sql | SqlPanel | phase 4 |
| A_IMPEXP | import/export | — | phase 4 |

---

## 8. Что удалится (после green smoke)

- `w.php`, `wx.php`, `r.php`, `filemgr.php`
- `GlobalBridge`
- frameset в `index.php` → redirect `/app`

---

## 9. Auth

- Cookie `dbs_jwt` (httpOnly, SameSite=Lax) для same-origin SPA
- `Authorization: Bearer` для API/smoke
- Header `X-Requested-With: DbscriptSPA` на mutating fetch

---

## 10. Как проверить

```bash
docker compose -f dev/docker-compose.yml up -d --build
docker compose exec web php scripts/arch-modern-install-dev.php testpass12
docker compose exec web php scripts/arch-modern-seed-demo.php
docker compose exec web bash scripts/smoke-api-auth.sh http://127.0.0.1
cd frontend && npm ci && npm run build
```

---

## 11. Non-goals v1

- Mobile app, offline PWA
- LIVEMOD inline
- Admin SPA (остаётся SSR)

---

## 12. Лицензия

Неофициальный форк для предложения архитектуры. Модель лицензии dj--alex не меняется.
