# modern-ops — servicectl, hardening, tests

Ветка **`modern-ops`** от **`php8-port`**: управление сервисами на Ubuntu/Debian, CSRF, SQL helpers, расширенные smoke/PHPUnit и CI. Продуктовая модель dj--alex (csv-конфиги, `dbscore.lib`, cookie `dbsa`) **не меняется**.

| | |
|---|---|
| База | `php8-port` |
| Push | `github` → `modern-ops` |
| Merge обратно | после green CI на `modern-ops` |

## Отличия от php8-port

| Область | Что добавлено |
|---------|----------------|
| **Servicectl** | `scripts/dbs-servicectl.sh`, `_conf/servicectl.cfg`, кнопки SU в `admin.php` |
| **CSRF** | `$_SESSION['dbs_csrf']`, hidden `_csrf`, opt-out `pr[77]=on` |
| **SQL** | `dbs_escape_*`, prepared audit log, `dbs_editor_insert()` |
| **Hardening** | cookie `HttpOnly`/`SameSite`, guard `info.php`, security headers в deploy examples |
| **Тесты** | +9 PHPUnit, `smoke-csrf.sh`, `smoke-servicectl.sh`, GHA `modern-ops-ci.yml` |

## Servicectl

```bash
bash scripts/dbs-servicectl.sh --probe
bash scripts/dbs-servicectl.sh --resolve db.restart
```

- Действия: `db.stop|start|restart`, `web.reload|restart`, `php-fpm.reload`
- **Opt-out:** без root / NOPASSWD sudo кнопки disabled, статус в admin
- Sudoers: [`deploy/dbscript-servicectl.sudoers.example`](deploy/dbscript-servicectl.sudoers.example)
- Шаблон cfg: [`deploy/servicectl.cfg.example`](deploy/servicectl.cfg.example) → `_conf/servicectl.cfg`

`EXEC_SHELL_CMD` → редактор `cmdlines` (`w.php?tbl=cmdlines`).

## CSRF

Включено по умолчанию после установки. Отключение: в admin → `pr77` (DIS_CSRF) = on, или вручную в `property.cfg`.

Smoke: `bash scripts/smoke-csrf.sh http://127.0.0.1`

## SQL

- **Prepared:** audit INSERT в `logwritesql()`
- **Dynamic editor SQL** — без Big Bang; helper `dbs_editor_insert()` для точечной миграции
- **`executesql()`** — denywords-модель автора сохранена

## Запуск тестов

```bash
docker exec dev-web-1 bash scripts/verify.sh
docker exec dev-web-1 vendor/bin/phpunit --testsuite unit
docker exec -e SMOKE_SKIP_INSTALL=1 dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1
bash tests/servicectl/run-matrix.sh
```

## Миграция prod

1. Скопировать `servicectl.cfg`, настроить sudoers при необходимости
2. HTTPS → cookie `Secure` включается автоматически
3. Закрыть `info.php` (по умолчанию 403 без SU/debug)
4. Прогнать `smoke-all.sh` на стенде

## Non-goals

- `password_hash` вместо `hashgen` / md5 gmdata
- Отказ от cookie `dbsa`
- ORM / PSR-4 / UTF-8 исходников

См. [`CHANGELOG-MODERN-OPS.md`](CHANGELOG-MODERN-OPS.md).
