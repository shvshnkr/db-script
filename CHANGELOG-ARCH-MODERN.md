# CHANGELOG — arch-modern

## Unreleased

### Added (phase 7)
- `DenywordsGuard` — `denywords.toml` + built-in blocklist (legacy parity)
- `EditorService::executeSql` — SELECT/write, single statement, denywords
- `ReaderService::export` — CSV (up to 5000 rows, respects search `q`)
- `w-arch.php?action=sql` — SQL console UI
- `r-arch.php?export=csv` — CSV download
- `verify.sh` — exclude `vendor/` from PHP 8 blocker grep
- Smoke: SQL select/denyword + CSV export

### Added (phase 5–6)
- `EditorService`: `getRow`, `createRow`, `updateRow`, `deleteRows` (PK via schema, composite `|`)
- `EditorController`: create/edit/delete forms + CSRF POST
- `ReaderService`: `search` (LIKE across columns), `viewRow`
- `ReaderController`, `r-arch.php`, Twig reader templates
- Smoke gate: CRUD create + `r-arch` search/view

### Added (continued)
- `EditorService::listTables` / `listRows`, `ConnectionFactory`, `DbdataRepository`
- `EditorController`, `w-arch.php`, Twig editor templates
- `GlobalBridge` hydrates legacy `$pr` / `$prdbdata` / `$prauth`
- `ServicectlService` wrapper for `dbs-servicectl.sh`
- `scripts/arch-modern-seed-demo.php` — demo table for Docker dev
- `dev/Dockerfile` + entrypoint: `pdo_mysql` for Doctrine DBAL

### Added (phase 4)
- `MessageCatalog`, `LangResolver`, `ThemeService`, `TwigFactory` with `t()` helper
- UTF-8 lang: `_langdb/english.json`, `_langdb/russian.json` (from cfg via convert script)
- Twig `layout/app.html.twig`, nav partial, themed CSS variables from `styles.toml`
- `LoginController`, `AdminController`, `logout-arch.php`
- `scripts/smoke-arch-modern.sh`, CI smoke gate updated
- `JwtAuthService`: lcobucci v5 `relatedTo()` for subject claim
- `composer.json`: `php-collective/toml` @dev + `composer.lock` updated
- PSR-4 autoload `Dbscript\` → `src/Dbscript/`
- `bootstrap.php`, `Application`, `TomlLoader`, `ConfigRepository`, `UserRepository`
- `JwtAuthService`, `CsrfService`, `AuthMiddleware`
- `install-arch.php`, `login-arch.php` (TOML + JWT path)
- `EditorService`, `ReaderService` API-ready stubs
- `GlobalBridge` (temporary legacy globals bridge)
- `TwigRenderer`
- [`ARCHITECTURE.md`](ARCHITECTURE.md), [`README-ARCH-MODERN.md`](README-ARCH-MODERN.md)
- TOML config examples in `deploy/toml/`
- GHA workflow `arch-modern-ci.yml`
- Dev helper `scripts/arch-modern-install-dev.php`

### Removed (planned — phase 6+)
- `dbscore.lib`, csv configs, `dbsa`, `hashgen` — arch-modern entry points no longer load legacy core; `w.php`/`r.php`/`install.php` remain parallel until final cutover

### Notes
- Legacy entry points still load `dbscore.lib` for `w.php`/`r.php`; use `*-arch.php` for TOML/JWT path.
- Fresh install will require TOML path (install rewrite in progress).
