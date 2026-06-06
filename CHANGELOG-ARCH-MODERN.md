# CHANGELOG — arch-modern

## Unreleased

### Added
- Branch `arch-modern` from `modern-ops`
- PSR-4 autoload `Dbscript\` → `src/Dbscript/`
- `bootstrap.php`, `Application`, `TomlLoader`, `ConfigRepository`, `UserRepository`
- `JwtAuthService`, `CsrfService` (skeleton)
- `EditorService`, `ReaderService` API-ready stubs
- `GlobalBridge` (temporary legacy globals bridge)
- `TwigRenderer`
- [`ARCHITECTURE.md`](ARCHITECTURE.md), [`README-ARCH-MODERN.md`](README-ARCH-MODERN.md)
- TOML config examples in `deploy/toml/`
- GHA workflow `arch-modern-ci.yml`

### Removed (planned)
- `dbscore.lib`, csv configs, `dbsa`, `hashgen` — final phases

### Notes
- Legacy entry points still load `dbscore.lib` until thin controllers land.
- Fresh install will require TOML path (install rewrite in progress).
