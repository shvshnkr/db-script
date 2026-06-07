# Changelog — arch-spa

## Unreleased

### Added

- Branch `arch-spa` from `arch-modern`
- REST API v1: `api/index.php`, `ApiKernel`, auth + editor endpoints
- JWT Bearer support in `JwtAuthService::readFromRequest`
- React SPA skeleton: `frontend/` (Login, AppShell, Editor wireframe)
- Design docs: `DESIGN-SPA.md`, `ARCHITECTURE-SPA.md`
- Smoke: `scripts/smoke-api-auth.sh`
- PHPUnit: `AuthApiTest`
- CI: `.github/workflows/arch-spa-ci.yml`
- Apache `.htaccess` routes for `/api/v1` and `/app`

### Planned (next phases)

- Editor CRUD UI (RecordForm, toolbar actions)
- Reader + File manager SPA
- Remove w.php / r.php / GlobalBridge after parity
- i18n from `_langdb`
- Handoff screenshots
