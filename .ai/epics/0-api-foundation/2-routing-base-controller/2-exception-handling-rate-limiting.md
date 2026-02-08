status: done

# Story 0.2: Prompt 2 -- API Exception Handling & Rate Limiting

## Context

Prompt 1 created the `ApiController` base class with response helpers (`successResponse`, `createdResponse`, `errorResponse`, `notFoundResponse`) and account scoping. The `routes/api.php` has a v1 prefix group with `auth:sanctum` middleware. The health check route works. However, when exceptions occur (validation failures, authorization errors, model not found, server errors), Laravel still returns its default HTML error pages or inconsistent JSON. API consumers need predictable JSON error responses for every error type.

## Goal

Configure API exception handling in `bootstrap/app.php` so that all API routes return consistent JSON error responses, and add rate limiting configuration for the API. After this prompt, every error that can occur on an API route returns structured JSON with the appropriate HTTP status code, and API requests are rate-limited.

## Non-Goals

- Do not modify the `ApiController` response helpers (those are already done)
- Do not create any resource-specific controllers or routes
- Do not add custom exception classes (use Laravel's built-in exceptions)
- Do not change exception handling for web routes -- only API routes should get JSON responses

## Constraints

- Exception handling goes in `bootstrap/app.php` inside the existing `withExceptions` callback -- this is the Laravel 12 pattern, not a custom exception handler class
- Use `$exceptions->shouldRenderJsonWhen()` or `$exceptions->render()` to target only API routes (check for `/api/` prefix or `request()->expectsJson()`)
- Error response format must match the `ApiController::errorResponse()` envelope: `{ "message": "...", "errors": { ... } }`
- Rate limiting configuration goes in `AppServiceProvider` using `RateLimiter::for()` -- follow Laravel conventions
- Rate limit the API at 60 requests per minute per user (token), consistent with Laravel defaults but explicitly configured
- The rate limiter key should be the authenticated user's ID so it's per-user, not per-IP

## Acceptance Criteria

- [ ] `bootstrap/app.php` `withExceptions` handles these API errors with JSON responses:
  - `ValidationException` returns 422 with `{ "message": "...", "errors": { "field": ["messages"] } }`
  - `AuthenticationException` returns 401 with `{ "message": "Unauthenticated." }`
  - `AuthorizationException` returns 403 with `{ "message": "Forbidden." }`
  - `ModelNotFoundException` returns 404 with `{ "message": "Resource not found." }`
  - Generic exceptions return 500 with `{ "message": "Server error." }` (no stack trace in production)
- [ ] Web routes still return normal Inertia/HTML error pages (not affected)
- [ ] `AppServiceProvider` defines an `api` rate limiter: 60 requests/minute per authenticated user
- [ ] `routes/api.php` v1 group applies the `throttle:api` middleware
- [ ] Test: POST to an API route with invalid data returns 422 with correct JSON structure
- [ ] Test: request to non-existent resource returns 404 with correct JSON structure
- [ ] Test: request without token returns 401 with correct JSON structure
- [ ] Test: rate limit headers are present in API responses (`X-RateLimit-Limit`, `X-RateLimit-Remaining`)
- [ ] All existing tests still pass (`php artisan test`)

---

## Tech Analysis

- **`bootstrap/app.php` `withExceptions` is currently empty** (line 28-29: just `//`). This is where Laravel 12 centralizes exception handling. The `$exceptions` parameter provides `render()`, `shouldRenderJsonWhen()`, and other methods for customizing error responses.
- **Laravel 12 exception rendering**: `shouldRenderJsonWhen()` lets you conditionally render JSON based on the request. Use `$request->is('api/*')` to target API routes specifically, so web routes keep their Inertia error pages.
- **`ValidationException` already has `$e->errors()`** which returns `['field' => ['messages']]` -- this maps directly to the desired error envelope. The `render()` callback just needs to format it.
- **`AuthenticationException`** is thrown by the `auth:sanctum` middleware when no valid token is present. By default Laravel redirects to login for web but returns 401 for API -- but the response format needs to match our envelope.
- **`ModelNotFoundException`** is thrown when route model binding fails (e.g., `GET /api/v1/colorways/999`). Need to catch this and return a clean 404 instead of a stack trace.
- **Rate limiting**: Laravel has a `throttle` middleware built in. The `AppServiceProvider` is the standard place to define named rate limiters via `RateLimiter::for('api', ...)`. The `platform/app/Providers/AppServiceProvider.php` exists and has an empty `boot()` method -- add the rate limiter there.
- **Web route middleware** already applies `HandleInertiaRequests` (line 22-26 of bootstrap/app.php), so Inertia error handling is separate and won't be affected.
- **Existing `api` middleware call** in bootstrap/app.php (line 20: `$middleware->api()`) already sets up the base API middleware stack. The throttle middleware needs to be applied at the route group level, not globally.

## References

- `platform/bootstrap/app.php` -- empty `withExceptions` callback to populate, existing middleware configuration to understand
- `platform/app/Http/Controllers/Api/ApiController.php` -- response format to match (created in Prompt 1)
- `platform/app/Providers/AppServiceProvider.php` -- where to add rate limiter configuration
- `platform/routes/api.php` -- add `throttle:api` middleware to v1 group (modified in Prompt 1)
- `platform/tests/Feature/Api/ApiControllerTest.php` -- existing API test patterns to follow (created in Prompt 1)

## Files

- Modify `platform/bootstrap/app.php` -- add JSON exception rendering for API routes in `withExceptions`
- Modify `platform/app/Providers/AppServiceProvider.php` -- add `RateLimiter::for('api', ...)` in `boot()`
- Modify `platform/routes/api.php` -- add `throttle:api` to the v1 middleware group
- Create `platform/tests/Feature/Api/ExceptionHandlingTest.php` -- tests for each error type (401, 403, 404, 422, 500) and rate limiting
