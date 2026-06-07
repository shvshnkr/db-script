# Dbscript 4 — карта веток форка shvshnkr

**Для:** внутренняя навигация и handoff dj--alex  
**Репозиторий:** [shvshnkr/db-script](https://github.com/shvshnkr/db-script)  
**Upstream (автор):** [dj--alex/db-script](https://github.com/dj--alex/db-script) · `master`  
**Обновлено:** 2026-06-07 (arch-spa tip [`4e4d313`](https://github.com/shvshnkr/db-script/commit/4e4d313))

> Файл в `_langdb/.archive/` — **служебная папка**, не участвует в runtime CMS и закрыта от веб-доступа (`.htaccess`).  
> Не ссылается из публичного `README.md` — только прямая ссылка на GitHub.

---

## 1. Цепочка веток

```
dj--alex/master (4.3.x, PHP 5/7)
        │
        ▼
   php8-port ──► modern-ops ──► arch-modern ──► arch-spa
   (порт PHP8)   (hardening)    (новая арх.)    (REST + SPA)
```

| Ветка | База | Назначение | Статус на GitHub |
|-------|------|------------|------------------|
| [`master`](https://github.com/shvshnkr/db-script/tree/master) | upstream dj--alex | зеркало оригинала, без порта | ✅ |
| [`php8-port`](https://github.com/shvshnkr/db-script/tree/php8-port) | `master` | механический порт на PHP 8.0+ | ✅ |
| [`modern-ops`](https://github.com/shvshnkr/db-script/tree/modern-ops) | `php8-port` | эксплуатация: CSRF, session-auth, servicectl | ✅ |
| [`arch-modern`](https://github.com/shvshnkr/db-script/tree/arch-modern) | `modern-ops` | черновик альтернативной архитектуры (PSR-4, TOML, JWT, Twig) | ✅ |
| [`arch-spa`](https://github.com/shvshnkr/db-script/tree/arch-spa) | `arch-modern` | REST `/api/v1` + React SPA `/app/*` | ✅ [`4e4d313`](https://github.com/shvshnkr/db-script/commit/4e4d313) |

---

## 2. Кратко по веткам

### `master` — оригинал dj--alex

| | |
|---|---|
| **Зачем** | Эталон автора (~4.3.x), PHP 5/7-эра, **не запускается на PHP 8** |
| **Что сделано** | Без изменений форка; точка отсчёта для `php8-port` |
| **Публичный README** | — (оригинал на [dj--alex/db-script](https://github.com/dj--alex/db-script)) |
| **Handoff** | — |

---

### `php8-port` — порт на PHP 8

| | |
|---|---|
| **Зачем** | Запустить legacy Dbscript на **PHP 8.0+** без смены архитектуры |
| **Что сделано** | `mysql_*` → `mysqli_*`; guards PHP 8; Docker + smoke + PHPUnit; версия ядра **4.5.0**; deploy examples |
| **Сохранено** | `dbscore.lib`, csv `*.cfg`, cookie `dbsa`, CP1251, все URL entry-файлов |
| **Последний коммит** | [`15fb611`](https://github.com/shvshnkr/db-script/commit/15fb611) — handoff в langdb archive |
| **Публичный README** | [`README.md`](https://github.com/shvshnkr/db-script/blob/php8-port/README.md) |
| **Handoff (подробно)** | [`handoff-djalex.ru.md`](https://github.com/shvshnkr/db-script/blob/php8-port/_langdb/.archive/php8-port-2026/handoff-djalex.ru.md) |

**Ключевые артефакты:** `dev/docker-compose.yml`, `scripts/smoke-all.sh`, `scripts/verify.sh`, `deploy/*.example`

---

### `modern-ops` — hardening и эксплуатация

| | |
|---|---|
| **Зачем** | Слой **prod-ops** поверх рабочего `php8-port` — без переписывания ядра |
| **Что сделано** | Session-auth (миграция с legacy `dbsa`); CSRF; `dbs_escape_*` / prepared audit; `dbs-servicectl.sh`; +9 PHPUnit; GHA `modern-ops-ci.yml` |
| **Сохранено** | csv-конфиги, `$pr`/`$prauth`, имя cookie `dbsa`, лицензия, CP1251 |
| **Последний коммит** | [`ddc9787`](https://github.com/shvshnkr/db-script/commit/ddc9787) — session auth, PHP 8 fixes, handoff |
| **Публичный README** | [`README-MODERN-OPS.md`](https://github.com/shvshnkr/db-script/blob/modern-ops/README-MODERN-OPS.md) |
| **Handoff (подробно)** | [`handoff-djalex.ru.md`](https://github.com/shvshnkr/db-script/blob/modern-ops/_langdb/.archive/modern-ops-2026/handoff-djalex.ru.md) |

**Ключевые артефакты:** `scripts/dbs-servicectl.sh`, `deploy/servicectl.cfg.example`, `deploy/dbscript-servicectl.sudoers.example`

---

### `arch-modern` — чистая архитектура (предложение)

| | |
|---|---|
| **Зачем** | Черновик перестройки Dbscript под современный PHP-стек — **для рассмотрения автором**, без обратной совместимости с csv/`dbscore.lib` |
| **Что сделано** | PSR-4 `src/Dbscript/`; TOML `_conf/*.toml`; JWT `dbs_jwt`; Twig SSR; `EditorService`/`ReaderService`; install/login/admin/w-arch/r-arch; SQL/denywords; CSV export |
| **Параллельно** | Legacy `dbscore.lib` + `*.cfg` + `w.php`/`r.php` ещё не вырезаны (фаза cutover) |
| **Последний коммит** | [`04bb5ce`](https://github.com/shvshnkr/db-script/commit/04bb5ce) — editor CRUD, reader, SQL/denywords, CSV export |
| **Публичный README** | [`README-ARCH-MODERN.md`](https://github.com/shvshnkr/db-script/blob/arch-modern/README-ARCH-MODERN.md) |
| **Архитектура** | [`ARCHITECTURE.md`](https://github.com/shvshnkr/db-script/blob/arch-modern/ARCHITECTURE.md) |
| **Handoff (подробно)** | [`handoff-djalex.ru.md`](https://github.com/shvshnkr/db-script/blob/arch-modern/_langdb/.archive/arch-modern-2026/handoff-djalex.ru.md) |

**Entry URLs (JWT + TOML):** `install-arch.php`, `login-arch.php`, `w-arch.php`, `r-arch.php`, `admin-arch.php`

**Фазы:** skeleton ✅ · TOML/install 🔄 · JWT ✅ · Twig/i18n ✅ · Editor CRUD ✅ · Reader/SQL/export ✅ · legacy cutover 🔄

---

### `arch-spa` — REST API + React SPA

| | |
|---|---|
| **Зачем** | Наследник `arch-modern`: backend services + **React SPA** вместо `w.php`/frameset |
| **Что сделано** | REST `/api/v1/*`; JWT auth API; Editor/Reader/Files/Converter/Info SPA; LIVEMOD inline; `ImportExportService` (fdb↔mysql); legacy `w.php`/`wx.php`/`r.php`/`filemgr.php` удалены; smoke `smoke-all-spa.sh`; CI `arch-spa-ci.yml` |
| **Последний коммит** | [`4e4d313`](https://github.com/shvshnkr/db-script/commit/4e4d313) — LIVEMOD, parity smoke, docs |
| **Публичный README** | [`README-ARCH-SPA.md`](https://github.com/shvshnkr/db-script/blob/arch-spa/README-ARCH-SPA.md) |
| **Архитектура** | [`ARCHITECTURE-SPA.md`](https://github.com/shvshnkr/db-script/blob/arch-spa/ARCHITECTURE-SPA.md) |
| **Handoff (подробно)** | [`arch-spa-2026/handoff`](https://github.com/shvshnkr/db-script/blob/arch-spa/_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md) |

**API:** auth, tables CRUD, reader, files, sql, converter, menu, i18n, info — [`openapi.yaml`](https://github.com/shvshnkr/db-script/blob/arch-spa/openapi.yaml)  
**SPA routes:** `/app/login`, `/app/editor`, `/app/reader`, `/app/files`, `/app/converter`, `/app/info/:slug`

**Gate:** `bash scripts/smoke-all-spa.sh http://127.0.0.1` (Docker dev stack)

---

## 3. Сводная таблица handoff

| Ветка | Handoff (RU, подробный) | Краткий README |
|-------|-------------------------|----------------|
| `php8-port` | [`php8-port-2026/handoff`](https://github.com/shvshnkr/db-script/blob/php8-port/_langdb/.archive/php8-port-2026/handoff-djalex.ru.md) | [`README.md`](https://github.com/shvshnkr/db-script/blob/php8-port/README.md) |
| `modern-ops` | [`modern-ops-2026/handoff`](https://github.com/shvshnkr/db-script/blob/modern-ops/_langdb/.archive/modern-ops-2026/handoff-djalex.ru.md) | [`README-MODERN-OPS.md`](https://github.com/shvshnkr/db-script/blob/modern-ops/README-MODERN-OPS.md) |
| `arch-modern` | [`arch-modern-2026/handoff`](https://github.com/shvshnkr/db-script/blob/arch-modern/_langdb/.archive/arch-modern-2026/handoff-djalex.ru.md) | [`README-ARCH-MODERN.md`](https://github.com/shvshnkr/db-script/blob/arch-modern/README-ARCH-MODERN.md) |
| `arch-spa` | [`arch-spa-2026/handoff`](https://github.com/shvshnkr/db-script/blob/arch-spa/_langdb/.archive/arch-spa-2026/handoff-djalex.ru.md) | [`README-ARCH-SPA.md`](https://github.com/shvshnkr/db-script/blob/arch-spa/README-ARCH-SPA.md) |

---

## 4. Что читать кому

| Аудитория | С чего начать |
|-----------|---------------|
| **dj--alex — порт PHP 8** | handoff `php8-port-2026` |
| **dj--alex — prod hardening** | handoff `modern-ops-2026` + `README-MODERN-OPS` |
| **dj--alex — новая архитектура** | handoff `arch-modern-2026` + `ARCHITECTURE.md` |
| **dj--alex — современный UI** | handoff `arch-spa-2026` + `DESIGN-SPA.md` + `openapi.yaml` |
| **Разработчик форка** | этот файл → handoff нужной ветки → smoke в `scripts/` |

---

## 5. Smoke / CI по веткам

| Ветка | Gate |
|-------|------|
| `php8-port` | `scripts/smoke-all.sh`, `scripts/verify.sh`, PHPUnit |
| `modern-ops` | + `smoke-csrf.sh`, `smoke-servicectl.sh`, GHA `modern-ops-ci.yml` |
| `arch-modern` | `scripts/smoke-arch-modern.sh`, GHA `arch-modern-ci.yml` |
| `arch-spa` | `scripts/smoke-all-spa.sh`, GHA `arch-spa-ci.yml` |

---

## 6. Лицензия и ограничения

Все ветки форка — **неофициальные** относительно релизов dj--alex.  
Оригинал: [`license.txt`](https://github.com/shvshnkr/db-script/blob/php8-port/license.txt) · сайт http://dj.chg.su/dbscript

`arch-modern` / `arch-spa` — **демо-архитектура**, Dbscript на этом форке **не в prod**.

---

*Этот файл — единая точка входа в документацию веток. Детали — в handoff каждой ветки.*
