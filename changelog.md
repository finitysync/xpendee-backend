# Changelog — Xpendee Backend

## [2026-05-13]

### Added
- `app/API/Controllers/ApiController.php` — Base controller with `successResponse()`, `errorResponse()`, and `paginatedResponse()` helper methods
- `app/Http/Middleware/EnsureJsonResponse.php` — Middleware jo har API request pe `Accept: application/json` header force karta hai

### Changed
- `config/cors.php` — CORS allowed origins update kiye: `localhost:3000`, `localhost:5173`, `xpendee.com`, `admin.xpendee.com`
- `config/sanctum.php` — Stateful domains update kiye (new format), expiration already `null` tha
- `bootstrap/app.php` — Sanctum `EnsureFrontendRequestsAreStateful` aur `EnsureJsonResponse` middleware API group mein prepend kiye; `auth.sanctum` alias add kiya
- `routes/api.php` — Saaf `/health` endpoint se replace kiya
- `.env` — `APP_NAME=Xpendee`, `APP_URL=http://localhost:8000`, DB MySQL se connect (database: `xpendee`, username: `root`), mail Resend SMTP pe set, `SANCTUM_STATEFUL_DOMAINS` add kiya
