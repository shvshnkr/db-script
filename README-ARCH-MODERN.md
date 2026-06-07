# arch-modern — clean architecture proposal for dj--alex

Ветка **`arch-modern`** от **`modern-ops`**: предложение новой архитектуры Dbscript 4 «как должно быть в 2026», **без** обратной совместимости с csv/`dbscore.lib`/`dbsa`.

| | |
|---|---|
| База | `modern-ops` |
| Push | `github` → `arch-modern` |
| Главный документ | [`ARCHITECTURE.md`](ARCHITECTURE.md) |
| Handoff | [`_langdb/.archive/arch-modern-2026/handoff-djalex.ru.md`](_langdb/.archive/arch-modern-2026/handoff-djalex.ru.md) |

## Зачем

Dbscript **нигде не используется** в prod на этом форке → можно показать dj--alex улучшенную модель без миграционного багажа.

## Что меняется vs modern-ops

| Область | modern-ops | arch-modern |
|---------|------------|-------------|
| Ядро | `dbscore.lib` (~5k строк) | PSR-4 `src/Dbscript/` |
| Конфиги | csv + `$pr[n]` | TOML + именованные поля |
| Auth | cookie `dbsa`, dual-mode | JWT `dbs_jwt`, `password_hash` only |
| UI | PHP templates + globals | Twig SSR (+ API-ready services) |
| Кодировка | CP1251 legacy | UTF-8 |
| Editor | SQL в `w.php` | `EditorService` (→ future REST) |

## Быстрый старт (dev)

```bash
git checkout arch-modern
composer install
# fresh install:
# open /install-arch.php  OR  php scripts/arch-modern-install-dev.php
docker compose -f dev/docker-compose.yml up -d --build
docker compose exec web bash scripts/smoke-all.sh http://127.0.0.1
```

## Стек

- PHP **8.2+**
- Composer: `lcobucci/jwt`, `doctrine/dbal`, `doctrine/orm`, `php-collective/toml`, `twig/twig`, `symfony/http-foundation`
- MySQL **utf8mb4**

## Фазы

1. ✅ Skeleton: composer, `bootstrap.php`, `Application`, docs
2. 🔄 TOML + install (`install-arch.php`, `InstallWriter`)
3. ✅ JWT + login (`login-arch.php`, `AuthMiddleware`)
4. ✅ Twig layouts + i18n (`MessageCatalog`, `TwigFactory`, `admin-arch.php`)
5. ✅ Editor CRUD (`EditorService`, `w-arch.php` forms)
6. ✅ Reader + SQL/export (`ReaderService`, `r-arch.php`, `executeSql`, CSV)
7. 🔄 Legacy cutover (`dbscore.lib`/`*.cfg` parallel); tests, CI, handoff

См. [`CHANGELOG-ARCH-MODERN.md`](CHANGELOG-ARCH-MODERN.md).

## Non-goals

- SPA / React
- Полный REST API (только контракт в ARCHITECTURE)
- Миграция старых `.cfg` установок
