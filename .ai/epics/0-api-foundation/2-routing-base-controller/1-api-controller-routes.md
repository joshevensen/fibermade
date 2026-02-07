status: done

# Story 0.2: Prompt 1 -- ApiController Base Class & Route Structure

## Context

Story 0.1 installed Sanctum and configured token-based authentication. The platform now has: Sanctum installed, the `sanctum` guard in `config/auth.php`, API middleware configured in `bootstrap/app.php`, a `CreateApiToken` Artisan command, and a minimal `routes/api.php` with a health check route (`GET /api/v1/health`). The `api:` parameter is registered in `withRouting()`. No API controllers or structured response layer exist.

## Goal

Create an `ApiController` base class that all API controllers will extend, and expand `routes/api.php` with a versioned `/v1/` prefix group structure. After this prompt, the API has a consistent JSON response format, automatic account scoping for queries, and a route file ready for resource controllers.

## Non-Goals

- Do not create any resource-specific API controllers (those are Stories 0.4-0.7)
- Do not create API Resources for JSON serialization (that's Story 0.3)
- Do not add exception handling to `bootstrap/app.php` (that's Prompt 2)
- Do not add rate limiting configuration (that's Prompt 2)
- Do not modify existing web controllers or routes

## Constraints

- `ApiController` goes in `app/Http/Controllers/Api/` to keep API controllers namespaced separately from web controllers
- Extend the existing `Controller` base class (`app/Http/Controllers/Controller.php`) which already has the `AuthorizesRequests` trait -- don't re-add it
- Account scoping pattern: use `$request->user()->account_id` to scope queries, matching the web controller pattern in `BaseController` (lines 27-29) where admin users see all records and regular users are scoped by `account_id`
- Response helpers should return `JsonResponse` instances with a consistent envelope: `{ "data": ... }` for success, `{ "message": "...", "errors": { ... } }` for errors
- The v1 route group should apply `auth:sanctum` middleware, matching the web routes' `['auth', 'verified']` pattern but for API context
- Use Pest syntax for tests (`test()` functions, `expect()` assertions) following the existing test patterns in `tests/Feature/`
- Keep the health check route from Story 0.1 intact

## Acceptance Criteria

- [ ] `app/Http/Controllers/Api/ApiController.php` exists with response helpers: `successResponse()`, `createdResponse()`, `errorResponse()`, `notFoundResponse()`
- [ ] `ApiController` has an `accountId()` method that returns the authenticated user's `account_id`
- [ ] `ApiController` has a `scopeToAccount()` method that takes a query builder and scopes it to the authenticated user's account (admins bypass scoping)
- [ ] `routes/api.php` has a `/v1/` prefix group with `auth:sanctum` middleware
- [ ] Health check route still works: `GET /api/v1/health` returns `{"status": "ok"}` with 200
- [ ] Tests verify each response helper returns the correct JSON structure and HTTP status code
- [ ] Tests verify account scoping logic (regular user is scoped, admin bypasses)

---

## Tech Analysis

- **Existing base controller** (`app/Http/Controllers/Controller.php`) is minimal -- just `abstract class Controller { use AuthorizesRequests; }`. ApiController should extend this to inherit authorization capabilities.
- **Account scoping pattern** is repeated across web controllers. `BaseController` (line 27-29) shows the pattern: admin users get unscoped queries, regular users filter by `account_id`. The ApiController should centralize this into a reusable method so API controllers don't repeat it.
- **`routes/api.php` already exists** (created in Story 0.1 Prompt 2) with a basic health check. It needs to be expanded with a proper v1 prefix group and middleware, while keeping the health check.
- **`bootstrap/app.php` already registers `api:`** in `withRouting()` (from Story 0.1). No change needed there.
- **Web route structure** uses `Route::prefix('creator')->middleware(['auth', 'verified'])->group(...)` in `routes/creator.php`. API routes should follow a similar prefix+middleware+group pattern but with `auth:sanctum` instead.
- **Test directory structure**: existing controller tests live in `tests/Feature/Http/Controllers/`. API tests should go in `tests/Feature/Api/` to match the controller namespace separation.

## References

- `platform/app/Http/Controllers/Controller.php` -- base class to extend
- `platform/app/Http/Controllers/BaseController.php` -- account scoping pattern (lines 27-29) and authorization pattern to replicate in API context
- `platform/routes/creator.php` -- route group structure to follow (prefix, middleware, group)
- `platform/routes/api.php` -- existing health check route to preserve and expand
- `platform/tests/Feature/Http/Controllers/BaseControllerTest.php` -- test style and patterns (Pest, actingAs, factories)
- `platform/app/Policies/BasePolicy.php` -- admin check pattern (`$user->is_admin === true`) used in scoping logic

## Files

- Create `platform/app/Http/Controllers/Api/ApiController.php` -- base class with response helpers and account scoping
- Modify `platform/routes/api.php` -- expand with v1 prefix group, `auth:sanctum` middleware, keep health check
- Create `platform/tests/Feature/Api/ApiControllerTest.php` -- tests for response helpers and account scoping
