# MAP — Dbscript 4 (dbscript4_djalex)

**Проект:** DBSCRIPT v4.3.x (c) dj--alex — web CMS с редактором таблиц, reader, file manager, admin.  
**Путь:** `C:\Users\user\projects\dbscript4_djalex`  
**Ветка:** `php8-port` · **Remote:** https://github.com/dj--alex/db-script.git

## Цель (текущая фаза)

Порт на **PHP 8.2** + **mysqli**; dev/test через **WSL + Docker Compose** (Apache + MySQL 8).

## Статус (2026-06-06)

| Этап | Статус |
|------|--------|
| Ветка `php8-port` | ✅ локально |
| `import_request_variables` → `extract()` | ✅ в изменённых entry + `dbscore.lib` |
| `mysql_*` → `mysqli_*` в `dbscore.lib` | ✅ в работе |
| Dev Docker (`dev/docker-compose.yml`) | ✅ добавлен, не запушен |
| `scripts/verify.sh` | ✅ синтаксис + grep-guards |
| Smoke install/login/editor | ⏳ после `setup-wsl.sh` |
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

```bash
# WSL
cd /mnt/c/Users/user/projects/dbscript4_djalex
bash scripts/setup-wsl.sh
# → http://localhost:8080/install.php
```

| Setting | Value |
|---------|-------|
| Web | http://localhost:8080 |
| MySQL host (inside compose) | `db` |
| MySQL root pass | `dbscript_root` |
| Database | `dbscript_test` |
| MySQL host port | `3307` |

Verify:

```bash
bash scripts/verify.sh
docker compose -f dev/docker-compose.yml exec web bash scripts/verify.sh
```

Rollback: `bash scripts/teardown-wsl.sh`

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
