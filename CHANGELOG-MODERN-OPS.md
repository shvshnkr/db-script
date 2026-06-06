# CHANGELOG — modern-ops

## Unreleased

### Added
- `scripts/dbs-servicectl.sh` — probe/resolve/execute для systemd/SysV
- PHP wrappers `dbs_servicectl_*`, `dbs_cmdline_run` в `dbscore.lib`
- CSRF: `dbs_csrf_*`, `csrfkey()`, интеграция в `submitkey()`
- SQL tier 2: `dbs_escape_value/ident`, `dbs_prepare`, prepared audit log
- `dbs_editor_insert()` helper
- Smoke: `smoke-csrf.sh`, `smoke-servicectl.sh`
- PHPUnit: EncodevID, StrUnix, Mycol, Maskapply, Xbasename, DbsEscape, Csrf, Servicectl, DbsLogtype
- GHA `.github/workflows/modern-ops-ci.yml`
- `README-MODERN-OPS.md`, deploy sudoers/servicectl examples

### Changed
- `admin.php` — servicectl вместо `/etc/init.d/*`
- `r.php` — cmdlines через `dbs_cmdline_run` + validation
- `info.php` — 403 без SU/debug
- Cookie `dbsa` — HttpOnly, SameSite=Lax, Secure on HTTPS
- `dbs_query()` — убран ложный escape всей строки запроса

### Security
- Security headers в nginx/apache examples
- `verify.sh` guard: no `/etc/init.d/` in admin
