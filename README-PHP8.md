# Dbscript 4 — PHP 8.2 port (dev guide)

Target: **PHP 8.2**, MySQL via **mysqli**. Dev/test via **Docker Compose**.

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

## Verify

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
