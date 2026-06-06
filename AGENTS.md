# Dbscript 4 — правила для агентов

**Проект:** DBSCRIPT v4 (c) **dj--alex** — PHP CMS/редактор БД.  
**Активная ветка:** `php8-port` → PHP **8.0+** (verified **8.2 LTS** + **8.3**; PHP 7 не поддерживается), MySQL через **mysqli**. Требования: [`README-PHP8.md`](./README-PHP8.md#требования-к-php).  
**Upstream:** https://github.com/dj--alex/db-script.git · **Push:** `github` → https://github.com/shvshnkr/db-script.git (`php8-port`)

| | |
|---|---|
| Человеческая карта | [`MAP.md`](./MAP.md) |
| Dev guide | [`README-PHP8.md`](./README-PHP8.md) |
| Локальная L0-карта | `AI/project-map.toml` (gitignored) |
| Worklog | `AI/cursorworklog.md` (gitignored) |

## Git — коммить и пушить сам

**Не ждать явной просьбы.** После каждой логически завершённой единицы работы:

1. `git add` только релевантные файлы (не `_conf/`, `_logs/`, `AI/`).
2. Короткий commit message — **why**, не перечень файлов.
3. `git push github php8-port` (или текущую ветку на настроенный push-remote), если remote доступен. `origin` (dj--alex) — 403 без доступа.

Исключение: секреты и локальный runtime (`_conf/*.cfg` с паролями) — **никогда** в git.

## Dev rules

- Малые сфокусированные диффы; стиль legacy PHP dj--alex (глобалы, `$pr`, csv-конфиги).
- Сначала корневая причина; не чинить симптом PHP 8 без проверки цепочки `dbscore.lib` → entry PHP.
- Ядро — **`dbscore.lib`** (~5k строк): не дублировать SQL/авторизацию в entry-файлах.
- После механического порта — `docker exec dev-web-1 bash scripts/verify.sh` (или curl/smoke с хоста Win).
- Кодировка: legacy **CP1251** в исходниках; charset MySQL — из конфига (`SET NAMES`).

## Экономия контекста (из Dahusim — умная, не тупая)

**Оптимально тратить токены** = не тратить на лишнее, но **не экономить** на первом точном чтении нужного файла/секции карты.

- Не делать repo-wide `explore` / `Task explore`, если задача покрыта `MAP.md` или `AI/project-map.toml`.
- Не перечитывать подсистемы из TOML **в той же сессии**, если карта актуальна.
- Точечный `Read` / `Grep` по путям из карты; `dbscore.lib` — **диапазоны**, не весь файл (~5300 строк).
- Субагент — только если задача реально параллельна; иначе точечные инструменты.
- **Новый чат** — на смену фазы (порт → smoke в Docker → прод), не на каждый мелкий фикс.

## Фиксация на ходу (anti-repeat search)

Если агент **второй раз** ищет тот же путь/паттерн/команду — **сразу зафиксировать**, чтобы третий раз не искал:

| Что повторялось | Куда писать |
|-----------------|-------------|
| Путь, команда, URL, env | `AI/project-map.toml` → секция `[discoveries]` |
| Универсально для всех сессий | `.cursor/rules/dbscript-agent-bootstrap.mdc` |
| Смена фазы / статуса | `MAP.md` + `[status]` в L0 |
| Итог сессии | append `AI/cursorworklog.md` |

Поля worklog: `maps_updated`, `map_sync` (`none` | `L0` | `L1-hot` | `bootstrap`), `next_step`.

## Исчерпание контекста

1. Append `AI/cursorworklog.md` с `next_step` и `blockers`.
2. Обновить `[status]` в `AI/project-map.toml`.
3. Предложить новый чат: *«Прочитай MAP.md и последнюю запись AI/cursorworklog.md — продолжай с …»*

## Task router

| Задача | Читать |
|--------|--------|
| Ориентация | [`MAP.md`](./MAP.md) → `AI/project-map.toml` (секции) |
| PHP 8 port / mysql→mysqli | `AI/subsystems/php8-port.toml` → `scripts/verify.sh` |
| Dev / Docker | `AI/subsystems/dev-docker.toml` → [`README-PHP8.md`](./README-PHP8.md) — **Win + Docker Desktop**, не WSL |
| Ядро / auth / SQL / csv | `AI/subsystems/core-runtime.toml` → `dbscore.lib` (grep + диапазон) |
| Install wizard | `install.php` + `_conf/property.cfg` schema via `csvopen` |
| Editor / reader / admin | `w.php`, `r.php`, `admin.php` (entry only) |
| Editor variant / DB linker | `wx.php`, `dblinker.php` — barewords ✅; smoke *(если)* |
| История сессий | `AI/cursorworklog.md` (последняя запись) |
| Условные задачи («если…») | [`MAP.md`](./MAP.md) → секция **«Если (будущим агентам)»** |

## AI (локальная карта, gitignored)

Папка [`AI/`](./AI/) — карта и журнал (см. [`AI/README.md`](./AI/README.md)). В репо: **AGENTS.md**, **MAP.md**. Локально (gitignored): **`AI/`**, **`.cursor/`** (в т.ч. bootstrap rule).

**Не усложнять** архитектуру вразрез с `invariants` в `AI/project-map.toml`.
