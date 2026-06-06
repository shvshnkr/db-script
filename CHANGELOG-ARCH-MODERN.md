# CHANGELOG — arch-modern

## Unreleased

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

### Removed (planned)
- `dbscore.lib`, csv configs, `dbsa`, `hashgen` — final phases

### Notes
- Legacy entry points still load `dbscore.lib` until thin controllers land.
- Fresh install will require TOML path (install rewrite in progress).
