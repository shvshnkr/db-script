# Dbscript 4 — PHP 8 port (dev guide)

Target: **PHP 8.0+** (verified **8.2 LTS** + **8.3**), MySQL via **mysqli**. Dev/test via **Docker Compose**.

## Требования к PHP

| | Версия |
|---|--------|
| **Проверено (prod gate, Docker dev)** | **PHP 8.2.x** (primary) · **8.3.x** (matrix) — `php:${PHP_VERSION}-apache` в `dev/Dockerfile` |
| **Рекомендуется для prod** | **PHP 8.2** LTS |
| **Также verified** | **PHP 8.3** — `scripts/test-php-matrix.sh` |
| **Минимум (ветка `php8-port`)** | **PHP 8.0+** |
| **Не поддерживается** | PHP 7.x и ниже (`mysql_*`, `each()`, magic quotes и др.) |

Расширения PHP (обязательные для работы):

| Расширение | Назначение |
|------------|------------|
| **mysqli** | MySQL (единственный поддерживаемый DB-драйвер после порта) |
| **mbstring** | строки / legacy CP1251 |
| **gd** | изображения в editor/filemgr |
| **zip** | архивы в filemgr (опционально на части сценариев) |

Проверка на сервере: `php -v` → 8.0+; `php -m` → mysqli, mbstring.  
В контейнере: `docker exec dev-web-1 php -v` и `bash scripts/verify.sh`.

### PHP version matrix (8.2 + 8.3)

Primary dev остаётся на **8.2**. Для проверки **8.3**:

```powershell
# быстро: L0 + PHPUnit на 8.2 и 8.3 (пересборка web)
bash scripts/test-php-matrix.sh --quick

# полный gate без wipe _conf на каждой версии
bash scripts/test-php-matrix.sh --full
```

Одна версия вручную:

```powershell
$env:PHP_VERSION="8.3"
docker compose -f dev/docker-compose.yml build web
docker compose -f dev/docker-compose.yml up -d
docker exec dev-web-1 php -v
```

## Quick start (Windows — основной)

Docker Desktop уже на машине разработчика. Поднимать стек **с Windows**, без WSL:

```powershell
cd C:\Users\user\projects\dbscript4_djalex
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" compose -f dev/docker-compose.yml up -d
```

Open: http://localhost:8080/install.php

Containers: `dev-web-1`, `dev-db-1`.

## Quick start (WSL — опционально)

Только если нет Docker Desktop на Windows или пользователь явно просит Linux-shell:

```bash
cd /mnt/c/Users/user/projects/dbscript4_djalex
bash scripts/setup-wsl.sh
```

Open: http://localhost:8080/install.php

MySQL inside Docker:

| Setting | Value |
|---------|-------|
| Host | `db` |
| User | `root` |
| Password | `dbscript_root` |
| Database | `dbscript_test` |

## Rollback

```bash
bash scripts/teardown-wsl.sh
```

Docker-only rollback (no apt changes):

```bash
docker compose -f dev/docker-compose.yml down -v --rmi local
```

Optional WSL snapshot (PowerShell, once before first setup):

```powershell
wsl --export Ubuntu C:\Users\user\backups\wsl-before-dbscript4.tar
```

## Testing layers (prod gate)

Pyramid: **L0** static → **L1** GET smoke → **L2** POST functional → **L3–L4** PHPUnit → **L5** security/links → **L6** manual dangerous ops.

Full matrix: [`docs/TEST-MATRIX.md`](docs/TEST-MATRIX.md).

### Full pre-prod run (wipes `_conf`, fresh install)

```powershell
docker exec dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
```

### Quick run (keep existing install)

```powershell
docker exec -e SMOKE_SKIP_INSTALL=1 dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
```

### PHPUnit (after `composer install` in container)

```powershell
docker exec dev-web-1 composer install --no-interaction
docker exec dev-web-1 vendor/bin/phpunit --testsuite unit
docker exec dev-web-1 vendor/bin/phpunit --testsuite integration
```

### Individual scripts

| Script | Layer |
|--------|-------|
| `scripts/verify.sh` | L0 (extended PHP 8 grep) |
| `scripts/smoke-install.sh` | L1–L2 install cycle |
| `scripts/smoke-curl.sh` | L1 quick |
| `scripts/smoke-admin-test.sh` | L1 self-test (Critical=0, Noncritical≤100) |
| `scripts/smoke-cold-paths.sh` | L1 cold GET + entrypoints |
| `scripts/smoke-security.sh` | L5 403 + install guard |
| `scripts/smoke-auth.sh` | L5 auth gates + logout |
| `scripts/smoke-index-router.sh` | L5 index router |
| `scripts/smoke-editor-crud.sh` | L2 denywords CRUD (w.php) |
| `scripts/smoke-wx-post.sh` | L2 denywords CRUD (wx.php) |
| `scripts/smoke-reader.sh` | L1–L2 reader |
| `scripts/smoke-filemgr-ops.sh` | L2 mkdir + rename |
| `scripts/smoke-dblinker.sh` | L2 DB list + table browse |
| `scripts/smoke-getfile-roundtrip.sh` | L2 getfile + _data file |
| `scripts/smoke-admin-save.sh` | L2 admin note/myprof |
| `scripts/smoke-acl.sh` | L4 limited user ACL |
| `scripts/smoke-links.sh` | L5 hub links |
| `scripts/smoke-news.sh` | L1 blog GET + optional POST |
| `scripts/smoke-post-logs.sh` | L5 _logs error scan |
| `scripts/test-php-matrix.sh` | PHP 8.2 + 8.3 compatibility matrix |

## Verify (L0 only)

Inside container (preferred on Windows):

```powershell
docker exec dev-web-1 bash scripts/verify.sh
docker exec dev-web-1 bash scripts/smoke-install.sh http://127.0.0.1
```

Or from WSL if you are already there: `bash scripts/verify.sh`.

## Manual smoke checklist

1. `install.php` — wizard completes without fatal errors
2. MySQL step — connection to host `db` succeeds
3. Admin user created — `_conf/*.cfg` files exist
4. Redirect to `login.php`
5. Login — session/cookie `dbsa`, main page loads
6. `w.php` — editor opens, POST works
7. `r.php` — reader/search
8. `admin.php` — settings
9. `_logs/log.dat` — written after actions
10. Direct URL `_conf/sitedata.cfg` — **403 Forbidden**
11. Remove `install.php` after install

## Port notes (php8-port branch)

- `import_request_variables` → `extract($_GET/POST/COOKIE)`
- `mysql_*` → `mysqli_*` in `dbscore.lib`
- `each()`, `split()`, `get_magic_quotes_*` removed
- `implode($glue, $array)` argument order fixed
- `initalize.php` loads `dbscore.lib` (was broken `dbscore.php`)
- Protected dirs: `_conf`, `_logs`, `_local`, `_data` (Apache deny in `dev/apache-vhost.conf`)

## Mechanical port script

```bash
php scripts/port-mechanical.php
git diff   # always review before commit
```
