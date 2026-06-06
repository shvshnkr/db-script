# TEST-MATRIX — Dbscript 4 prod gate

Матрица «функция → тест → слой». Статус обновлять после `scripts/smoke-all.sh`.

## Требования к окружению

| Компонент | Версия |
|-----------|--------|
| **PHP (проверено)** | **8.2.x** (LTS, primary dev) · **8.3.x** (matrix) — Docker, `smoke-all.sh`, PHPUnit |
| **PHP (минимум)** | **8.0+** на ветке `php8-port`; PHP 7.x не поддерживается |
| **PHP (рекомендуется prod)** | **8.2** LTS; **8.3** — verified через `scripts/test-php-matrix.sh` |
| **MySQL** | 8.0 (dev: `mysql:8.0` в Compose) |
| **Расширения PHP** | mysqli, mbstring; gd, zip — для полного функционала |

Подробнее: [`README-PHP8.md`](../README-PHP8.md#требования-к-php).

## PHP version matrix

| PHP | Роль | L0 verify | L3–L4 PHPUnit | L0–L5 smoke | Команда |
|-----|------|-----------|---------------|-------------|---------|
| **8.2** | primary dev / prod gate | ✅ | ✅ | ✅ `smoke-all.sh` | default `dev/docker-compose.yml` |
| **8.3** | current stable (non-LTS) | ✅ | ✅ | ✅ `--full` | `bash scripts/test-php-matrix.sh --full` |
| **8.0–8.1** | минимум по коду | ✅ (L0) | ✅ (L3) | — | совместимость по `version_compare`; не в CI matrix |

Быстрый прогон матрицы (L0 + PHPUnit, без HTTP smoke):

```powershell
docker exec dev-web-1 bash scripts/test-php-matrix.sh --quick
# или с хоста (пересборка web на каждую версию):
bash scripts/test-php-matrix.sh --quick
```

Полный gate на обе версии (~7–10 мин):

```powershell
bash scripts/test-php-matrix.sh --full
```

Переменная `PHP_VERSION` для одной версии: `PHP_VERSION=8.3 docker compose -f dev/docker-compose.yml build web && docker compose -f dev/docker-compose.yml up -d`.

## Слои

| Слой | Инструмент | Назначение |
|------|------------|------------|
| L0 | `scripts/verify.sh` | `php -l`, grep PHP 8 blockers (расширенный) |
| L1 | smoke GET | Страницы открываются без fatal |
| L2 | smoke POST | CRUD, search, file/DB ops |
| L3 | PHPUnit unit | Pure helpers в `dbscore.lib` |
| L4 | PHPUnit integration + ACL | verify.sh, limited user |
| L5 | smoke security/links/auth/logs | 403, router, hub, auth gates, _logs scan |
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
| Cold paths wx/dblinker/filemgr + entrypoints | L1 | `smoke-cold-paths.sh` | auto |
| Protected dirs 403 | L5 | `smoke-security.sh` | auto |
| Auth gates (no cookie, wrong pass, logout) | L5 | `smoke-auth.sh` | auto |
| index.php ?w/?r/?f router | L5 | `smoke-index-router.sh` | auto |
| Editor cfg CRUD | L2 | `smoke-editor-crud.sh` | auto |
| wx.php POST CRUD | L2 | `smoke-wx-post.sh` | auto |
| Reader .ver/.help + POST | L2 | `smoke-reader.sh` | auto |

## P1 — высокий приоритет

| Сценарий | Слой | Скрипт | Статус |
|----------|------|--------|--------|
| filemgr mkdir + rename | L2 | `smoke-filemgr-ops.sh` | auto |
| dblinker SHOW DATABASES + USE db | L2 | `smoke-dblinker.sh` | auto |
| getfile roundtrip | L2 | `smoke-getfile-roundtrip.sh` | auto |
| admin myprof + note | L2 | `smoke-admin-save.sh` | auto |
| Hub link crawl (35 links) | L5 | `smoke-links.sh` | auto |
| news/nedit GET + optional POST | L1/L2 | `smoke-news.sh` | auto |
| Limited user ACL | L4 | `smoke-acl.sh` | auto |
| Post-smoke _logs scan | L5 | `smoke-post-logs.sh` | auto |
| hashgen / strdbstounixtime / cmddecode | L3 | PHPUnit Unit | auto |
| prefixdecode / testadmin / mysql parse / Clear_array_empty / checkboxcorrect | L3 | PHPUnit Unit | auto |
| verify.sh via PHPUnit | L4 | `SmokeScriptsTest` | auto |

## P2 — по возможности

| Сценарий | Слой | Скрипт | Статус |
|----------|------|--------|--------|
| Blog POST add row | L2 | `smoke-news.sh` (optional POST) | auto (skip if no sd[38]) |
| info.php phpinfo exposure | L5 | `smoke-cold-paths.sh` | auto (dev only warning) |
| wx POST parity | L2 | `smoke-wx-post.sh` | auto |

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
```

1. `smoke-all.sh` → exit 0  
2. `admin.php?cmd=test` → `Critical: 0`, `Noncritical ≤ 100`  
3. PHPUnit unit + integration → exit 0  
4. L6 checklist подписан для ops из таблицы выше  

## Быстрый прогон (без wipe `_conf`)

```powershell
docker exec -e SMOKE_SKIP_INSTALL=1 dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
```
