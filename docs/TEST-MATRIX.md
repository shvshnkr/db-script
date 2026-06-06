# TEST-MATRIX — Dbscript 4 prod gate

Матрица «функция → тест → слой». Статус обновлять после `scripts/smoke-all.sh`.

## Слои

| Слой | Инструмент | Назначение |
|------|------------|------------|
| L0 | `scripts/verify.sh` | `php -l`, grep PHP 8 blockers |
| L1 | smoke GET | Страницы открываются без fatal |
| L2 | smoke POST | CRUD, search, file/DB ops |
| L3 | PHPUnit unit | Pure helpers в `dbscore.lib` |
| L4 | PHPUnit integration | verify.sh, HTTP (optional) |
| L5 | smoke security/links | 403, index router, hub crawl |
| L6 | manual | DEL_DB, EXEC_SHELL, RAR, prod flock |

## P0 — блокирует prod

| Сценарий | Слой | Скрипт / тест | Статус |
|----------|------|---------------|--------|
| PHP syntax + grep guards | L0 | `verify.sh` | auto |
| Fresh install 1–9 | L2 | `smoke-install.sh` | auto |
| Login + cookie `dbsa` | L2 | `smoke-install.sh` | auto |
| `_conf/*.cfg` manifest | L2 | `smoke-install.sh` | auto |
| w.php editor GET | L1 | `smoke-install.sh` | auto |
| r.php reader GET | L1 | `smoke-install.sh` | auto |
| admin.php GET | L1 | `smoke-install.sh` | auto |
| admin self-test A_T_CRIT=0 | L1 | `smoke-admin-test.sh` | auto |
| Cold paths wx/dblinker/filemgr | L1 | `smoke-cold-paths.sh` | auto |
| Protected dirs 403 | L5 | `smoke-security.sh` | auto |
| index.php ?w/?r/?f router | L5 | `smoke-index-router.sh` | auto |
| Editor cfg CRUD | L2 | `smoke-editor-crud.sh` | auto |
| Reader .ver/.help + POST | L2 | `smoke-reader.sh` | auto |

## P1 — высокий приоритет

| Сценарий | Слой | Скрипт | Статус |
|----------|------|--------|--------|
| filemgr mkdir | L2 | `smoke-filemgr-ops.sh` | auto |
| dblinker SHOW DATABASES | L2 | `smoke-dblinker.sh` | auto |
| admin myprof + note | L2 | `smoke-admin-save.sh` | auto |
| Hub link crawl | L5 | `smoke-links.sh` | auto |
| news/nedit GET | L1 | `smoke-news.sh` | auto |
| hashgen / strdbstounixtime / cmddecode | L3 | PHPUnit Unit | auto |

## P2 — по возможности

| Сценарий | Слой | Скрипт | Статус |
|----------|------|--------|--------|
| Blog POST add row | L2 | `smoke-news.sh` (POST) | manual |
| Limited user ACL denial | L4 | PHPUnit ACL | todo |
| wx POST parity | L2 | subset editor | todo |

## L6 — только ручной чеклист

| Операция | Риск | Проверка |
|----------|------|----------|
| `DEL_DB` | destructive | staging only |
| `EXEC_SHELL_CMD` | RCE | never in auto |
| `MYSQL_REBOOT` / `APACHE_REBOOT` | downtime | manual |
| `FMG_UNRAR` / `FMG_RAR` | OS-specific | Linux manual |
| Prod `dbs_flock()` без `DBSCRIPT_DEV_NO_FLOCK` | lock stalls | VPS deploy |

## Prod gate (Definition of Done)

```powershell
docker exec dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
docker exec dev-web-1 vendor/bin/phpunit --testsuite unit
```

1. `smoke-all.sh` → exit 0  
2. `admin.php?cmd=test` → `Critical: 0`  
3. L6 checklist подписан для ops из таблицы выше  

## Быстрый прогон (без wipe `_conf`)

```powershell
docker exec -e SMOKE_SKIP_INSTALL=1 dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
```
