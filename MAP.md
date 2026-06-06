# MAP — Dbscript 4 (dbscript4_djalex)

**Проект:** DBSCRIPT v4.3.x (c) dj--alex — web CMS с редактором таблиц, reader, file manager, admin.  
**Путь:** `C:\Users\user\projects\dbscript4_djalex`  
**Ветка:** `php8-port` · **Remote:** https://github.com/dj--alex/db-script.git

## Цель (текущая фаза)

Порт на **PHP 8.2** + **mysqli**; dev/test через **Docker Compose** (Apache + MySQL 8) на **Windows (Docker Desktop)** — контейнеры уже подняты, WSL не нужен.

## Статус (2026-06-06)

| Этап | Статус |
|------|--------|
| Ветка `php8-port` | ✅ локально |
| `import_request_variables` → `extract()` | ✅ в изменённых entry + `dbscore.lib` |
| `mysql_*` → `mysqli_*` в `dbscore.lib` | ✅ в работе |
| Dev Docker (`dev/docker-compose.yml`) | ✅ добавлен, не запушен |
| `scripts/verify.sh` | ✅ синтаксис + grep-guards |
| Smoke install/login/editor | ✅ full wizard (MySQL `db`/`dbscript_root`) → login → `w.php` |
| Agent map / worklog | ✅ AGENTS.md, MAP.md, `.cursor/rules/` |

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
| Docker CLI (Win) | `C:\Program Files\Docker\Docker\resources\bin\docker.exe` |

Verify / smoke:

```powershell
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" exec dev-web-1 bash scripts/verify.sh
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" exec dev-web-1 bash scripts/smoke-install.sh http://127.0.0.1
```

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
