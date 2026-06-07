# Changelog — arch-spa

## Unreleased

### Added (phases 3–6)

- **Reader API + SPA:** `ReaderApiController`, `/app/reader/:tableId`, search/view/export CSV
- **File manager API + SPA:** `FileManagerService`, `FilesCfgRepository`, `/app/files` (list/upload/download/delete)
- **SQL panel:** `POST /api/v1/sql/execute`, `SqlPanel` in editor toolbar
- **Info pages:** `GET /api/v1/info/{slug}` (.ver, .info, .author, .help), `/app/info/:slug`
- **Menu API:** `GET /api/v1/menu`, dynamic sidebar in `AppShell`
- **i18n API:** `GET /api/v1/i18n`, `I18nProvider`, language switcher
- **Entry redirect:** `index.php` → `/app/` (legacy query preserved where possible)
- PHPUnit: `SpaApiPhaseTest`
- Extended `scripts/smoke-spa.sh` (menu, i18n, files, reader, sql)

### Removed (phase 6)

- `w.php`, `wx.php`, `r.php`, `filemgr.php`
- `src/Dbscript/Http/GlobalBridge.php`
- frameset `index.php` (replaced with SPA redirect)

### Added (phases 0–2)

- Branch `arch-spa` from `arch-modern`
- REST API v1: `api/index.php`, `ApiKernel`, auth + editor endpoints
- JWT Bearer support in `JwtAuthService::readFromRequest`
- React SPA: `frontend/` (Login, AppShell, Editor CRUD + bulk delete)
- Design docs: `DESIGN-SPA.md`, `ARCHITECTURE-SPA.md`
- Smoke: `scripts/smoke-api-auth.sh`
- PHPUnit: `AuthApiTest`, `EditorApiTest`
- CI: `.github/workflows/arch-spa-ci.yml`
- Apache `.htaccess` routes for `/api/v1` and `/app`

### Non-goals (v1)

- Full legacy `importexporttbl()` converter (A_IMPEXP multi-table exchange)
- LIVEMOD inline editing
- Admin SPA (SSR `admin-arch.php` only)
