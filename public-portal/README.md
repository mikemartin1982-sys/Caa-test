# CAA Public Site & Client Portal (PHP / Laravel)

Public marketing site, Public Calendar (calendar/map/list views), session
detail pages, the "New Client Account" inquiry form, and the authenticated
Client Portal. This layer owns UI only — it never touches a database
directly for business domain data. Every session, enrollment,
certification, and client record goes through `ComplianceEngineClient`,
which implements `api-contract/openapi.yaml` 1:1.

## Honesty note on verification

Packagist is not reachable from this build environment (same
`host_not_allowed` restriction as Maven Central), so **this could not be
built with a real `composer install`** — no actual Laravel framework
classes are present, so this can't be booted or run here.

What *was* verified instead: **every one of the 10 PHP files passes `php
-l` (syntax check) with zero errors.** That confirms the PHP itself is
syntactically valid — it does not confirm the Laravel-specific APIs
(`Http::baseUrl(...)`, route model binding, `$request->user()`, etc.) are
used correctly, since that requires the actual framework classes to
type-check against.

Run `composer install` yourself once you have normal internet access,
then `php artisan serve` — I'd expect this to need only minor adjustment
(routes/web.php references controllers that exist, the client class is
self-contained), but please verify before relying on it, same caveat as
the Java service.

## What's here

| Path | Covers | Doc section |
|---|---|---|
| `app/Services/ComplianceEngine/ComplianceEngineClient.php` | The ONLY way PHP touches business data — one method per OpenAPI path | 2 |
| `app/Http/Controllers/PublicCalendarController.php` | Calendar/map/list views, Public sessions only | 4e |
| `app/Http/Controllers/SessionDetailController.php` | Public session detail page | 4d |
| `app/Http/Controllers/InquiryController.php` | "New Client Account" form — does NOT create a Client | 3c |
| `app/Http/Controllers/Portal/DashboardController.php` | Client Portal home | 4e |
| `app/Http/Controllers/Portal/EnrollmentController.php` | Enrollment + outside-client self-authorization | 4, 4e |
| `routes/web.php` | Public routes + `auth`-gated `portal.*` routes | — |

## What's deliberately NOT here yet

- Blade view templates (`resources/views/...`) — controllers reference
  them (`view('public.calendar')` etc.) but the templates themselves
  aren't scaffolded. Next natural step once the API contract is stable.
- Laravel's own auth scaffolding (migrations for `users`, Breeze/Fortify,
  etc.) — the portal's `auth` middleware assumes this exists but it isn't
  built here, since it's standard Laravel boilerplate rather than
  CAA-specific logic.
- The Session Details / Roster **admin** side (staff-facing, not client
  portal) — this scaffold covers the public site and Client Portal only.
- VR enrollment path in `EnrollmentController` — noted in the docblock as
  following the same pattern once the VR-specific endpoints are needed.

## Key design point worth remembering when extending this

`ComplianceEngineClient::authorizeOutsideClient()` deliberately surfaces
the API's 409 response as `ComplianceEngineConflictException` rather than
a generic error — that 409 is the real enforcement of "Private sessions
are structurally locked to the host, Semi-Private is what unlocks
self-service" (Section 4). Any new caller of that endpoint should catch
that exception specifically, the way `Portal\EnrollmentController` does,
rather than letting it bubble up as a raw 500.
