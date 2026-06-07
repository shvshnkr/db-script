# CHANGELOG — modern-ops

## Unreleased

### Added
- `scripts/dbs-servicectl.sh` — probe/resolve/execute для systemd/SysV
- PHP wrappers `dbs_servicectl_*`, `dbs_cmdline_run` в `dbscore.lib`
- CSRF: `$_SESSION['dbs_csrf']`, hidden `_csrf`, opt-out `pr[77]=on`
- Session auth: token cookie `dbsa` (64 hex) + legacy base64 migration
- Password helpers: `dbs_password_verify`, `dbs_password_hash`, bcrypt on password change
- SQL tier 2: `dbs_escape_*`, `dbs_prepare`, prepared audit log, `dbs_editor_insert()`
- `dbs_cfg_version_float()` — корректное сравнение версии property.cfg
- Handoff: `_langdb/.archive/modern-ops-2026/handoff-djalex.ru.md`
- Smoke: `smoke-csrf.sh`, `smoke-servicectl.sh`
- PHPUnit: EncodevID, StrUnix, Mycol, Maskapply, Xbasename, DbsEscape, Csrf, Servicectl, DbsLogtype, DbsAuthSession, DbsPassword, …
- GHA `.github/workflows/modern-ops-ci.yml`
- `README-MODERN-OPS.md`, deploy sudoers/servicectl examples

### Changed
- `admin.php` — servicectl вместо `/etc/init.d/*`; session cookie on profile save
- `w.php`, `wx.php`, `filemgr.php` — `dbs_lock_adm`, `dbs_require_csrf`
- `filemgr.php` — POST+CSRF delete, zip-slip guard, `escapeshellarg` for shell
- `getfile.php`, `login.php` — `dbs_require_basic_auth`
- `r.php` — cmdlines через `dbs_cmdline_run` + validation
- `info.php` — 403 без SU/debug
- Cookie `dbsa` — HttpOnly/SameSite; session token вместо base64 password
- `dbs_query()` — убран ложный escape всей строки запроса
- `scripts/smoke-wx-post.sh` — CSRF parity с `smoke-editor-crud.sh`
- `footer.php`, `dbscore.lib` — PHP 8 null/warning guards (vercfg, xfgetcsv, checkbox, xbasename, errorlog, CLI boot)

### Fixed
- Warning `non-numeric value` на `$vercfg-$vernumb` (`property.cfg` header `4.5.0`)
- PHPUnit warnings: `checkboxcorrect`, `xbasename`, `readfullcsv` / `$xfgetlimit`
- CLI smoke ACL setup — без HTML-шума (`installermode`, skip `head.php`/`onend`)

### Security
- Security headers в nginx/apache examples
- `verify.sh` guard: no `/etc/init.d/` in admin
- `dbs_reject_adm_override()` — блок подмены ADM

## Non-goals

- `password_hash` для всех существующих gmdata без смены пароля
- Отказ от cookie `dbsa`
- ORM / PSR-4 / UTF-8 исходников
