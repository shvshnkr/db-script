# MAP — Dbscript 4 (dbscript4_djalex)

**Проект:** DBSCRIPT v4.3.x (c) dj--alex — web CMS с редактором таблиц, reader, file manager, admin.  
**Путь:** `C:\Users\user\projects\dbscript4_djalex`  
**Ветка:** `php8-port` · **Push remote:** `github` → https://github.com/shvshnkr/db-script.git · **Upstream:** https://github.com/dj--alex/db-script.git

## Цель (текущая фаза)

**Prod-testing** — многослойное тестирование перед prod: L0 verify → L1–L2 smoke HTTP → L3–L4 PHPUnit → L5 security/links. **PHP 8.2 LTS** (primary) + **8.3** (matrix), мин. **8.0+** на `php8-port`, **mysqli**, Docker Compose на **Windows (Docker Desktop)**. WSL не нужен.

## Требования

| | |
|---|---|
| PHP (проверено) | **8.2.x** (primary) · **8.3.x** (matrix) |
| PHP (минимум) | **8.0+** · PHP 7.x не поддерживается |
| MySQL | 8.0 |
| Расширения | mysqli, mbstring, gd, zip |
| Документация | [`README-PHP8.md`](./README-PHP8.md#требования-к-php) |

## Статус (2026-06-06)

| Этап | Статус |
|------|--------|
| Ветка `php8-port` | ✅ локально, синхрон с `github/php8-port` |
| `import_request_variables` → `extract()` | ✅ |
| `mysql_*` → `mysqli_*` в `dbscore.lib` | ✅ (verify grep green; в коде только комментарии) |
| Dev Docker (`dev/docker-compose.yml`) | ✅ в репо, контейнеры `dev-web-1` / `dev-db-1` |
| `scripts/verify.sh` | ✅ синтаксис + grep-guards |
| Smoke full cycle | ✅ install → login → `w.php` → `r.php` → `admin.php` |
| Barewords `admin.php` / `w.php` / `filemgr.php` | ✅ `scripts/fix-barewords.php` + ручные правки |
| `admin.php?cmd=test` (`testcfgs`) | ✅ implode/null guards, init vars, typo fixes |
| Dev flock bind-mount | ✅ `DBSCRIPT_DEV_NO_FLOCK=1` + `dbs_flock()` |
| Barewords `wx.php` / `dblinker.php` / `readfilemenu.php` | ✅ `fix-barewords.php` |
| Barewords `dbscore.lib` / `main.php` / `str0.php` | ✅ `fix-barewords.php` |
| `settype()` bareword types | ✅ quoted in dbscore.lib, w/wx, readfilemenu, classAudioFile |
| Smoke cold paths (wx/dblinker/filemgr/getfile/main) | ✅ `scripts/smoke-cold-paths.sh` |
| `filemgr.php` + `$dbdataskip` | ✅ prdbdata init, fileforaction array guard |
| **Многослойное тестирование** | ✅ `scripts/smoke-all.sh` + `docs/TEST-MATRIX.md` |
| **PHP version matrix 8.2 + 8.3** | ✅ `scripts/test-php-matrix.sh`, `PHP_VERSION` build arg |
| smoke-lib + L2/L5 scripts | ✅ security, index-router, editor-crud, reader, filemgr, dblinker, admin-save, links, news |
| PHPUnit L3 unit | ✅ `composer.json`, `tests/Unit/*` |
| **Следующее** | L4 integration HTTP tests; L6 manual checklist sign-off; prod deploy |
| Agent map / worklog | ✅ AGENTS.md, MAP.md в репо; `.cursor/`, `AI/*` локально |

## Entry points (HTTP)

| URL / файл | Роль |
|------------|------|
| `index.php` | Роутер query → `r.php` / `w.php` / `admin.php` / `filemgr.php` |
| `install.php` | Мастер установки (до первого `_conf/`) |
| `login.php` | Авторизация, cookie `dbsa` |
| `w.php` | **Editor** — запись в таблицы |
| `wx.php` | Editor (variant) |
| `r.php` | **Reader** — просмотр/поиск |
| `admin.php` | Настройки сайта |
| `filemgr.php` | Файловый менеджер |
| `main.php` | Help / reports |
| `dblinker.php` | DB linker utility |

## Ядро (всегда через require)

```
*.php  →  require_once('dbscore.lib')
dbscore.lib  →  _conf/property.cfg  (csvopen/readfullcsv)
             →  extract($_GET/POST/COOKIE)
             →  mysqli_connect / sql* wrappers
             →  auth, cmsg, bluescreen, onend
```

**Критично:** typo **`initalize.php`** (не initialize) — legacy имя; грузит `dbscore.lib`.

## Данные на диске

| Путь | Назначение |
|------|------------|
| `_conf/` | Конфиги (`property.cfg`, sitedata) — **403 в dev Apache** |
| `_logs/` | `log.dat` |
| `_local/` | Локальные данные |
| `_data/` | User data |
| `_langdb/` | Языковые `.cfg` |
| `_templates/`, `_style/` | UI |

## Dev environment

**Windows (основной путь):** Docker Desktop уже установлен; работаем с контейнерами **напрямую из PowerShell**, без WSL.

```powershell
cd C:\Users\user\projects\dbscript4_djalex
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" compose -f dev/docker-compose.yml up -d
# → http://localhost:8080/install.php
```

| Setting | Value |
|---------|-------|
| Web | http://localhost:8080 |
| Containers | `dev-web-1`, `dev-db-1` |
| MySQL host (inside compose) | `db` |
| MySQL root pass | `dbscript_root` |
| Database | `dbscript_test` |
| MySQL host port | `3307` |
| Dev no-flock | `DBSCRIPT_DEV_NO_FLOCK=1` in compose (bind-mount stalls) |
| Docker CLI (Win) | `C:\Program Files\Docker\Docker\resources\bin\docker.exe` |

Verify / smoke:

```powershell
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" exec dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" exec -e SMOKE_SKIP_INSTALL=1 dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" exec dev-web-1 vendor/bin/phpunit --testsuite unit
```

| Скрипт | Когда |
|--------|-------|
| `smoke-all.sh` | **Prod gate** — все слои L0–L5 |
| `verify.sh` | После каждого PHP-диффа (L0) |
| `smoke-curl.sh` | Быстрая проверка уже установленного сайта |
| `smoke-install.sh` | Полный цикл (пересоздаёт `_conf`) |
| `smoke-admin-test.sh` | `admin.php?cmd=test`, **A_T_CRIT=0** |
| `smoke-cold-paths.sh` | wx → dblinker → filemgr → getfile → main |
| `smoke-security.sh` | 403 на `_conf`/`_logs`, install guard |
| `smoke-editor-crud.sh` | POST add/del denywords.cfg |
| `docs/TEST-MATRIX.md` | Матрица функция → слой → статус |
| `scripts/test-php-matrix.sh` | PHP 8.2 + 8.3 — verify, PHPUnit, optional smoke |
| `fix-barewords.php` | Механика кавычек для `cmsg`/`lprint`/`rmsg`/`submitkey` |

Rollback (только Docker):

```powershell
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" compose -f dev/docker-compose.yml down -v
```

**WSL — опционально**, не предлагать и не запускать `setup-wsl.sh`, если Docker на Windows уже работает. Скрипты `scripts/setup-wsl.sh` / `teardown-wsl.sh` — для чистой Linux-среды, не для текущей машины разработчика.

## PHP 8 port checklist

- `scripts/port-mechanical.php` — механика; **всегда** `git diff` перед commit
- Guards: no `import_request_variables`, `mysql_*`, `each()`, `split()`, `get_magic_quotes_*`
- `implode($glue, $array)` — правильный порядок аргументов

## Агенту

1. `AGENTS.md` — git, tokens, anti-repeat
2. `.cursor/rules/dbscript-agent-bootstrap.mdc` — пути без поиска
3. `AI/project-map.toml` — L0 (локально)
4. `AI/cursorworklog.md` — последняя запись при handoff

Не сканировать репо целиком, если ответ в карте.

## Если (будущим агентам)

Условные задачи — **не блокируют** текущий smoke-цикл; брать только при симптоме или явном запросе.

| Если… | Действие |
|--------|----------|
| `admin.php` / curl **>60 с, 0 bytes** в dev | Зависший Apache worker после `flock` на bind-mount → `docker restart dev-web-1` или `compose up -d` |
| Деплой **без** bind-mount (prod/VPS) | **Если** нужна блокировка cfg — убрать `DBSCRIPT_DEV_NO_FLOCK` и проверить `dbs_flock()` / `LOCK_SH` vs `LOCK_EX` в `csvopen` (раньше было `flock(..., 3)` = `LOCK_UN`) |
| Smoke падает на **wx.php** / **dblinker.php** | Barewords уже quoted; добавить `scripts/smoke-wx.sh` / curl GET с cookie после login |
| Fatal на **main.php**, **str0.php**, help | `fix-barewords.php` на файл; там остались `lprint(OVERLOAD)` и т.п. |
| Fatal в редких ветках **dbscore.lib** | ~7 bareword `cmsg`/`lprint` (OVERLOAD, NOUSRS, ER_CFG, LOG_L_5, …) — `fix-barewords.php dbscore.lib` + ручной diff |
| Предупреждения **Undefined** в login/footer при прямом curl без cookie | Ожидаемо для unauthenticated hit; не путать с smoke-install (cookie flow) |
| Push **403** на `origin` (dj--alex) | Push на `github` (shvshnkr fork); см. worklog blockers |
| Нужен bareword-sweep по всему репо | `php scripts/fix-barewords.php file1.php …`; затем `verify.sh` + smoke-install |
