# Dbscript 4 — handoff после порта на PHP 8

**Для:** dj--alex  
**От:** порт ветки `php8-port` (форк [shvshnkr/db-script](https://github.com/shvshnkr/db-script), upstream — [dj--alex/db-script](https://github.com/dj--alex/db-script))  
**Дата:** 2026-06-07  
**Проверено:** PHP **8.2 LTS** (primary), **8.3** (matrix); минимум **8.0+**. PHP 7.x не поддерживается.

> Этот файл лежит в `_langdb/.archive/` — служебная папка, не участвует в работе CMS.  
> Прямая ссылка на GitHub: см. коммит в ветке `php8-port`.

---

## 1. Итоговый отчёт: что, где и зачем

### Контекст

Оригинальный `master` (последний ваш коммит ~4.3.x, PHP 5/7-эра) **не запускался на PHP 8** из‑за удалённых API и изменённой семантики языка. Порт сделан **механически + точечными правками по smoke-тестам**, без переписывания архитектуры: глобалы, `$pr`, csv-конфиги, `dbscore.lib` как ядро — всё на месте.

Версия в ядре поднята до **4.5.0** (`dbscore.lib`, поле `$verchar`).

### Сводка по слоям

| Слой | Что добавлено/изменено | Зачем |
|------|------------------------|-------|
| **Ядро** `dbscore.lib` | `mysql_*` → `mysqli_*`; `extract()` вместо `import_request_variables`; guards `??` / `!empty()`; barewords; flock bypass в dev | PHP 8 fatal при boot и SQL |
| **Entry PHP** | `install.php`, `login.php`, `w.php`, `wx.php`, `r.php`, `admin.php`, `filemgr.php`, `dblinker.php`, `main.php`, `str0.php`, … | HTTP-точки входа, те же URL |
| **Шаблоны** | `head.php`, `footer.php`, `_templates/head.php` | null-safe `$_SERVER`, boot до `_conf/` |
| **Классы** | `classAudioFile.php` | `settype(..., 'string')` вместо bareword |
| **Dev/CI** | `dev/`, `scripts/verify.sh`, `scripts/smoke-*.sh`, `composer.json`, `tests/` | Проверка без ручного кликанья |
| **Deploy** | `deploy/apache-site.conf.example`, `deploy/nginx-site.conf.example` | Закрыть `_conf`, `_logs`, `_local`, `_data` |
| **Документация** | `README.md` (публичный форк) | Установка, требования, smoke |

### Файлы ядра и entry — по пунктам

#### `dbscore.lib` (~610 строк diff от `master`)

- **`import_request_variables("PGC","")`** → `extract(array_merge($_GET, $_POST, $_COOKIE), EXTR_SKIP)` — единственный способ получить `$cmd`, `$table` и т.д. из query/post как раньше.
- **Весь `mysql_*`** переведён на **`mysqli_*`** (connect, query, fetch, escape, num_rows). Обёртки `sqlquery()`, `sqlfetch()` и др. сохранили прежние имена — entry-файлы по-прежнему вызывают «sql*», не mysqli напрямую.
- **`SET NAMES`** — без изменения логики: charset из `$globalencode` / конфига, не hardcode utf8.
- **Null-safe boot:** `$pr=($data===-1)?array():$data[0]`, флаг `$fresh_install` — install wizard работает **до** появления `property.cfg`.
- **`$_SERVER` / `$_ENV` / `$_GET`:** доступ через `?? ''` и `!empty()` — иначе PHP 8.0+ Warning → Exception в strict режимах.
- **`strpos(...)==true`** → `!==false` где нужно (Windows detection).
- **Bareword-константы** в `cmsg('KEY')`, `lprint('KEY')`, `rmsg('KEY')` — в PHP 8 неопределённая константа = fatal.
- **`settype($x, integer)`** → **`settype($x, 'integer')`** (и аналоги) — тип как строка.
- **`implode($arr, $glue)`** → **`implode($glue, $arr)`** — порядок аргументов в PHP 7.4+ только glue-first.
- **`each()`**, **`split()`**, **magic quotes** — удалены/вырезаны скриптом `scripts/port-mechanical.php`.
- **`dbs_flock()`:** env **`DBSCRIPT_DEV_NO_FLOCK=1`** в Docker (bind-mount + flock = зависания). На prod с локальным диском flock не трогали.

#### Entry-файлы (типовые правки)

| Файл | Суть правок |
|------|-------------|
| `install.php` | parse error (лишняя `}`); null-safe include ядра; wizard до `_conf/` |
| `login.php` | мелкие guards, совместимость с новым boot |
| `w.php`, `wx.php` | barewords `cmsg`/`lprint`/`submitkey`; `settype`; implode; **w.php:** bareword `end` → `'end'` |
| `r.php` | **`viewid`** → **`vID`** (имя переменной в коде) |
| `admin.php` | barewords; **`fclose` только если resource**; `admin.php?cmd=test` — guards null/implode; `msgexiterror("notrights",...)` — строки в кавычках |
| `filemgr.php` | barewords; init `$prdbdata`; guard массива `$fileforaction`; GET без параметров — fatal исправлен |
| `dblinker.php` | barewords; mysqli-вывод ошибок |
| `main.php`, `str0.php`, `readfilemenu.php`, `nedit.php`, `window.php` | bareword-sweep |
| `initalize.php` | typo **initalize** намеренно **не переименовывали** — legacy имя, на него завязаны include |

#### Что добавлено в репозиторий (новое, не ломает prod)

```
dev/docker-compose.yml, dev/Dockerfile, dev/apache-vhost.conf
scripts/verify.sh, scripts/smoke-all.sh, scripts/smoke-*.sh
scripts/port-mechanical.php, scripts/fix-barewords.php
scripts/test-php-matrix.sh   # PHP 8.2 + 8.3
composer.json, phpunit.xml.dist, tests/
deploy/*.example
README.md
```

#### Коммиты ветки `php8-port` (хронология, кратко)

1. Dev stack Docker + agent workflow  
2. Install path: null-safe boot, head template  
3. Mechanical port + verify green  
4. HTTP smoke: install → login → w → r  
5. admin.php в smoke + barewords admin/w  
6. Runtime hardening: settype, flock, admin test  
7. Cold paths: wx, dblinker, filemgr, getfile, main  
8. Layered smoke-all + PHPUnit skeleton  
9. PHP 8.3 matrix  
10. Публичный README; agent-only docs убраны из git (остались локально у maintainer форка)

### Что **не** меняли намеренно

- Лицензия, закрытость `dbscore.lib`, csv-формат конфигов.  
- Имена файлов (`initalize.php`, `dbscore.lib`).  
- CP1251 в исходниках.  
- Legacy shell-команды в `admin.php` (`/etc/init.d/apache2` и т.п.) — на Nginx не работали и раньше.  
- PHP-файлы в `_templates/` с `$`-подстановками — не трогали массово (риск поломать шаблоны).

### Статус проверок (на момент handoff)

| Проверка | Результат |
|----------|-----------|
| `scripts/verify.sh` | green (syntax + grep-guards) |
| `scripts/smoke-all.sh` | green (~210s full, ~12s skip-install) |
| PHPUnit unit | 23 теста OK |
| PHP matrix 8.2 + 8.3 | green (`test-php-matrix.sh --quick`) |

**Не закрыто автоматом:** L6 ручной чеклист редких UI-веток; prod deploy на вашем хостинге.

---

## 2. Памятка по PHP: что изменилось **в контексте Dbscript**

Ниже — не учебник PHP, а **«что сломается в твоём коде»** при следующей правке.

### 2.1. Удалённые функции (grep в `scripts/verify.sh`)

| Было (PHP 5/7) | Стало в Dbscript | Где смотреть |
|----------------|------------------|--------------|
| `import_request_variables("PGC")` | `extract(array_merge($_GET,$_POST,$_COOKIE), EXTR_SKIP)` | `dbscore.lib` boot |
| `mysql_connect`, `mysql_query`, … | `mysqli_*` внутри `sqlquery()` и др. | `dbscore.lib` ~1020+ |
| `each($_POST)` в `while` | `foreach ($_POST as $k => $v)` | mechanical port |
| `split($sep, $str)` | `explode($sep, $str)` | mechanical port |
| `get_magic_quotes_gpc()` | блоки удалены | quote_smart и др. |
| `ereg*` | уже было `preg_*` в вашем changelog 4.5 | редко |
| `create_function()` | не использовалось | — |
| `session_register()` и др. | не использовалось | — |

**Правило:** после любой правки — `bash scripts/verify.sh` (в Docker: `docker exec dev-web-1 bash scripts/verify.sh`).

### 2.2. Bareword-константы — главный сюрприз PHP 8

**Было (PHP 5 — «константа по умолчанию»):**
```php
echo cmsg(SAVE_OK);
lprint(OVERLOAD);
submitkey("form", SUBMIT);
```

**Стало:**
```php
echo cmsg('SAVE_OK');
lprint('OVERLOAD');
submitkey("form", 'SUBMIT');
```

В PHP 8 `SAVE_OK` без кавычек = **fatal: Undefined constant**.

**Где правили:** `admin.php`, `w.php`, `wx.php`, `filemgr.php`, `dblinker.php`, `dbscore.lib`, `main.php`, `str0.php`, `readfilemenu.php`.

**Инструмент:** `php scripts/fix-barewords.php путь/к/file.php` — потом ручной diff (скрипт не 100%).

**Типичные ключи:** `OVERLOAD`, `NOUSRS`, `ER_CFG`, `LOG_L_5`, `notrights`, все UPPER_CASE в `cmsg`/`lprint`/`rmsg`/`submitkey`.

### 2.3. `settype()` — второй аргумент только строкой

```php
settype($x, integer);   // FATAL в PHP 8
settype($x, 'integer'); // OK
```

Затронуто: `dbscore.lib`, `w.php`, `wx.php`, `readfilemenu.php`, `classAudioFile.php`.

### 2.4. Доступ к несуществующим ключам массива / null

PHP 8.0+ часто превращает Warning в исключение (зависит от `error_reporting` / handler).

**Паттерны порта:**
```php
$pr[36]          → ($pr[36] ?? '')
$_GET['crc']     → !empty($_GET['crc'])
$_SERVER['X']    → ($_SERVER['X'] ?? '')
if ($arr)        → if (!empty($arr))   // для cookie/session
```

**Особый случай — `$pr` до установки:** ядро создаёт пустой `$pr` и `$fresh_install`, чтобы `install.php` не падал.

### 2.5. `implode()` — порядок аргументов

```php
implode($lines, "\n");      // deprecated → исправлено
implode("\n", $lines);      // правильно
```

Часто всплывает в **`admin.php?cmd=test`** (`testcfgs`).

### 2.6. Ресурсы и файлы

```php
fclose($fp);                          // fatal если $fp = false
if (is_resource($fp ?? null)) fclose($fp);
```

`str0.php`: guard перед `fopen` пустого имени.  
`admin.php`: множество `@fopen` + проверки.

### 2.7. Двойной `extract()`

Некоторые entry-файлы вызывают `extract()` **после** `require dbscore.lib`, где extract уже был. Обычно безвредно (`EXTR_SKIP`), но при добавлении переменных следить за порядком boot:

```
*.php → require dbscore.lib → csvopen property.cfg → extract superglobals → auth → logic
```

### 2.8. mysqli vs mysql — для отладки

Снаружи по-прежнему:
```php
sqlquery($connect, $cmd);
$row = sqlfetch($result);
```

Внутри — mysqli. При ошибке смотреть `sqlerr($connect)` / mysqli error, не искать `mysql_*`.

### 2.9. Короткие теги `<?`

В репо в основном `<?php`. Если добавите файл с `<?` — на PHP 8 нужен `short_open_tag=On` (не рекомендуется). Ваш `changelog.txt` 4.5 уже планировал `<?` → `<?php`.

### 2.10. Кодировка

- **Файлы .php** — historically **Windows-1251**.  
- **MySQL** — charset из установки / `$globalencode`, `SET NAMES` в connect.  
- Не перекодируйте репо в UTF-8 без плана — сломаете строки в `_langdb/*.cfg`.

---

## 3. Подсказки по сопровождению (если PHP давно не трогали)

### 3.1. С чего начать после паузы

1. Клонировать форк, ветка **`php8-port`**:  
   `git clone -b php8-port https://github.com/shvshnkr/db-script.git`
2. Поднять Docker (Windows: Docker Desktop):  
   `docker compose -f dev/docker-compose.yml up -d`  
   → http://localhost:8080/install.php
3. Прогнать gate:  
   `docker exec dev-web-1 bash scripts/smoke-all.sh http://127.0.0.1`
4. Открыть **`dbscore.lib`** только через grep + нужный диапазон строк (~5300 строк — не читать целиком).

### 3.2. Ментальная модель (та же, что 15 лет назад)

```
index.php → роутер
entry (w/r/admin/…) → require dbscore.lib
dbscore.lib → _conf/property.cfg ($pr) → SQL → шаблоны _templates/
```

Глобальные `$pr`, `$prauth`, `$connect`, `$cmd` — **не баг, а стиль проекта**.

### 3.3. Безопасный цикл правки

```
1. Маленький diff в одном entry или в dbscore.lib
2. scripts/verify.sh
3. scripts/smoke-curl.sh  (быстро, сайт уже установлен)
4. или smoke-all.sh с SMOKE_SKIP_INSTALL=1
5. commit
```

Не править «на глаз» PHP 8 fatal — сначала URL из smoke, потом редкие ветки.

### 3.4. Типичные симптомы и куда смотреть

| Симптом | Вероятная причина | Действие |
|---------|-------------------|----------|
| `Undefined constant XXX` | bareword в cmsg/lprint | fix-barewords.php + кавычки |
| `mysqli_* expects parameter` | null connect / пустой SQL | install, `_conf/dbdata.cfg` |
| White screen на install | `$pr` до cfg | boot `$fresh_install` в dbscore.lib |
| admin test падает | implode / null cfg | admin.php testcfgs |
| filemgr на пустом GET | необъявленный массив | filemgr.php guards |
| Зависание записи cfg в Docker | flock | `DBSCRIPT_DEV_NO_FLOCK=1` только dev |

### 3.5. Prod deploy

1. PHP **8.2+**, расширения **mysqli, mbstring**, желательно **gd, zip**.  
2. Закрыть HTTP: `_conf`, `_logs`, `_local`, `_data` — шаблоны в `deploy/`.  
3. Права на запись: `_conf`, `_logs`, `_data`, `_local`.  
4. **`install.php` удалить** после установки.  
5. **`info.php`** закрыть или удалить на prod.  
6. **`DBSCRIPT_DEV_NO_FLOCK`** на prod **не** включать без причины.  
7. Порт **не официальный релиз** — прогнать smoke на копии БД.

### 3.6. Что можно улучшать постепенно (не обязательно сразу)

- Ручной L6-чеклист редких экранов (news, nedit, window, help).  
- Больше PHPUnit на чистые функции из `dbscore.lib` (hashgen, prefixdecode уже есть).  
- Опционально: CSRF, prepared statements — **отдельный проект**, не смешивать с PHP8-port.  
- Синхронизация с upstream `dj--alex/db-script` — только если автор откроет совместимость; сейчас форк живёт отдельно.

### 3.7. Связь форк ↔ оригинал

| | URL |
|--|-----|
| Оригинал | https://github.com/dj--alex/db-script (`master`) |
| PHP 8 форк | https://github.com/shvshnkr/db-script (`php8-port`) |
| Сайт автора | http://dj.chg.su/dbscript |

Лицензия — `license.txt`: некомmercial, копирайты сохранять.

---

## 4. Карта ссылок: PHP и Dbscript по годам

Удобно держать под рукой при «а почему так» — официальная документация PHP на русском где есть.

### Ключевые изменения PHP (migration guides)

| Год / версия | Тема | Ссылка |
|--------------|------|--------|
| **PHP 7.0** | Удалены mysql_*, ereg, старый foreach edge cases | https://www.php.net/manual/en/migration70.php |
| **PHP 7.2** | object|null, несчётные параметры | https://www.php.net/manual/en/migration72.php |
| **PHP 7.4** | implode order deprecated, curly braces offset | https://www.php.net/manual/en/migration74.php |
| **PHP 8.0** | **Breaking: barewords, named args, match, JIT off topic** | https://www.php.net/manual/en/migration80.php |
| **PHP 8.1** | readonly, enums, `$GLOBALS` restrictions | https://www.php.net/manual/en/migration81.php |
| **PHP 8.2** | dynamic properties deprecated, `utf8_encode` deprecated | https://www.php.net/manual/en/migration82.php |
| **PHP 8.3** | typed class constants, `json_validate` | https://www.php.net/manual/en/migration83.php |

### Функции, которые трогали в Dbscript

| Функция | Документация |
|---------|--------------|
| `extract()` | https://www.php.net/manual/ru/function.extract.php |
| `mysqli_connect` | https://www.php.net/manual/ru/mysqli.construct.php |
| `mysqli_query` | https://www.php.net/manual/ru/mysqli.query.php |
| `implode` | https://www.php.net/manual/ru/function.implode.php |
| `settype` | https://www.php.net/manual/ru/function.settype.php |
| Удалённые mysql_* | https://www.php.net/manual/en/function.mysql-connect.php *(removed)* |

### Внутри репозитория (ветка php8-port)

| Что | Путь |
|-----|------|
| Установка и требования | `README.md` |
| Проверка синтаксиса / grep | `scripts/verify.sh` |
| Полный smoke | `scripts/smoke-all.sh` |
| Механический порт | `scripts/port-mechanical.php` |
| Barewords | `scripts/fix-barewords.php` |
| PHP 8.2 + 8.3 matrix | `scripts/test-php-matrix.sh` |
| Apache / Nginx пример | `deploy/*.example` |
| Docker dev | `dev/docker-compose.yml` |
| Ваш старый план 4.5 | `changelog.txt` |
| **Этот handoff** | `_langdb/.archive/php8-port-2026/handoff-djalex.ru.md` |

### Рекомендуемый порядок чтения migration docs (если вспоминать PHP)

1. [migration80](https://www.php.net/manual/en/migration80.php) — **обязательно** (barewords, warnings→exceptions).  
2. [migration74](https://www.php.net/manual/en/migration74.php) — implode, offsets.  
3. [migration70](https://www.php.net/manual/en/migration70.php) — mysql/ereg (у вас в changelog уже набросок).  
4. 8.1–8.3 — по мере необходимости, для Dbscript критичнее 8.0–8.2.

---

## Быстрая шпаргалка одной строкой

**Dbscript на PHP 8 = тот же csv+глобалы+dbscore.lib, но: mysqli вместо mysql, extract вместо import_request_variables, все MESSAGE_KEY в кавычках, settype с строковым типом, implode(glue, arr), ?? на $_SERVER/$pr, verify.sh + smoke-all перед prod.**

---

*Конец handoff. Вопросы по форку — через maintainer ветки `php8-port`; merge в официальный репозиторий — по вашей лицензии и согласованию.*
