# Dbscript 4 — PHP 8

**DBSCRIPT v4** — веб-CMS с редактором и читателем MySQL-таблиц, админкой, файловым менеджером и настраиваемыми шаблонами. Исходный код и лицензия — **dj--alex**; ветка `php8-port` в этом репозитории — неофициальный порт под современный PHP.

| | |
|---|---|
| Оригинал | [dj--alex/db-script](https://github.com/dj--alex/db-script) (`master`) |
| Этот форк | [shvshnkr/db-script](https://github.com/shvshnkr/db-script) (`php8-port`) |
| Официальный сайт автора | http://dj.chg.su/dbscript |

## Лицензия и ограничения

Полный текст — [`license.txt`](license.txt) (авторский, без изменений).

Кратко:

- **Автор:** dj--alex · dj--alex@ya.ru  
- **Использование:** некоммерческое, **не для юридических лиц** (организаций).  
- **Копирайты и информация об авторе** удалять нельзя.  
- Перепродажа копий запрещена; выкладка — со ссылкой на официальный сайт.  
- Ядро частично закрыто (`dbscore.lib`); форк не меняет лицензионную модель автора.

Порт на PHP 8 **не является официальным релизом** dj--alex. Проверяйте на своём стенде перед prod.

Расширения ветки **`modern-ops`** (servicectl, CSRF, hardening) — [`README-MODERN-OPS.md`](README-MODERN-OPS.md).

## Что даёт ветка `php8-port`

- PHP **8.0+** (проверено: **8.2 LTS**, **8.3**), драйвер **mysqli** вместо устаревшего `mysql_*`.
- Удалены/заменены конструкции, несовместимые с PHP 8 (`import_request_variables`, `each()`, `split()`, magic quotes и др.).
- Docker Compose для локальной разработки, smoke-тесты и PHPUnit.
- Примеры конфигурации **Apache** и **Nginx** для закрытия служебных каталогов.

История правок автора — [`changelog.txt`](changelog.txt).

## Требования

| Компонент | Версия |
|-----------|--------|
| PHP | **8.0+** (рекомендуется **8.2** LTS) |
| MySQL / MariaDB | 5.7+ / 10.x (dev: MySQL 8.0) |
| Веб-сервер | Apache **или** Nginx + PHP-FPM |

Расширения PHP: **mysqli**, **mbstring**; для полного функционала — **gd**, **zip**.

Кодировка исходников — legacy **Windows-1251**; charset БД задаётся при установке (`SET NAMES` из конфига).

## Установка

1. Разместите файлы в document root (права на запись в `_conf/`, `_logs/`, `_data/`, `_local/`).
2. Настройте виртуальный хост (см. [Веб-сервер](#веб-сервер)).
3. Откройте **`/install.php`** — мастер создаст `_conf/*.cfg` и администратора.
4. Войдите через **`login.php`**, проверьте `w.php` (редактор), `r.php` (читатель), `admin.php`.
5. **Удалите `install.php`** после успешной установки.

Точка входа по умолчанию — **`index.php`** (роутинг query → `w.php` / `r.php` / `admin.php` / `filemgr.php`).

## Веб-сервер

Обязательно **запретить HTTP-доступ** к каталогам с конфигами и данными:

`_conf/`, `_logs/`, `_local/`, `_data/`

Готовые шаблоны:

| Сервер | Файл |
|--------|------|
| Apache | [`deploy/apache-site.conf.example`](deploy/apache-site.conf.example) |
| Nginx + PHP-FPM | [`deploy/nginx-site.conf.example`](deploy/nginx-site.conf.example) |

Dev-образ Docker использует Apache: [`dev/apache-vhost.conf`](dev/apache-vhost.conf).

### Ограничения по серверу

- В **`admin.php`** есть команды перезапуска **Apache/MySQL через shell** (`/etc/init.d/...`) — на Nginx/без root они не работают; это legacy-поведение оригинала, не часть порта.
- **`info.php`** (phpinfo) — отключите или закройте на prod.
- На bind-mount в Docker возможны задержки из‑за `flock` на cfg; в dev включён `DBSCRIPT_DEV_NO_FLOCK=1` (см. `dev/docker-compose.yml`). На prod с локальным диском flock обычно нормален.

## Разработка и проверка

```bash
docker compose -f dev/docker-compose.yml up -d
# → http://localhost:8080/install.php
```

MySQL в compose: host `db`, user `root`, password `dbscript_root`, database `dbscript_test`, порт **3307** на хосте.

```bash
# синтаксис + grep-guards PHP 8
docker exec dev-web-1 bash scripts/verify.sh

# полный smoke (свежая установка)
docker exec dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1

# без переустановки
docker exec -e SMOKE_SKIP_INSTALL=1 dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1

# PHPUnit
docker exec dev-web-1 composer install --no-interaction
docker exec dev-web-1 vendor/bin/phpunit
```

Проверка на PHP 8.2 и 8.3: `bash scripts/test-php-matrix.sh --quick`.

Откат dev-стека: `docker compose -f dev/docker-compose.yml down -v`.

## Структура (основное)

| Путь | Назначение |
|------|------------|
| `dbscore.lib` | Ядро: SQL, auth, csv-конфиги, шаблоны |
| `initalize.php` | Legacy-имя; подключает ядро |
| `w.php`, `wx.php` | Редактор |
| `r.php` | Читатель / поиск |
| `admin.php` | Настройки сайта |
| `filemgr.php` | Файловый менеджер |
| `_conf/` | Конфигурация (не отдавать по HTTP) |
| `_templates/`, `_style/` | UI |

## Контрибьюторы порта

Порт и инфраструктура тестирования — ветка `php8-port` форка [shvshnkr/db-script](https://github.com/shvshnkr/db-script). Изменения в открытом коде, достойные включения, могут быть переданы автору оригинала по правилам [`license.txt`](license.txt).
