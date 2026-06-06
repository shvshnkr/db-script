# CHANGELOG — arch-modern

## Unreleased

### Added (continued)
- `EditorService::listTables` / `listRows`, `ConnectionFactory`, `DbdataRepository`
- `EditorController`, `w-arch.php`, Twig editor templates
- `GlobalBridge` hydrates legacy `$pr` / `$prdbdata` / `$prauth`
- `ServicectlService` wrapper for `dbs-servicectl.sh`
- `scripts/arch-modern-seed-demo.php` — demo table for Docker dev
- `dev/Dockerfile` + entrypoint: `pdo_mysql` for Doctrine DBAL

### Fixed
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
