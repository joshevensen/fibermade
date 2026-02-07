status: done

# Story 0.1: Prompt 2 -- Token Creation Command & Auth Tests

## Context

Sanctum is now installed and configured (Prompt 1). The User model has `HasApiTokens`, the `sanctum` guard exists in `config/auth.php`, and API middleware is configured in `bootstrap/app.php`. No API routes exist yet, but we need a way to create tokens and verify that authentication works.

## Goal

Create an Artisan command that generates Sanctum API tokens for a user, and write Pest tests that verify token authentication works (authenticated request succeeds, unauthenticated request is rejected). The command is the Stage 1 token management mechanism -- no UI needed.

## Non-Goals

- Do not build a token management UI or API endpoint for token creation
- Do not create API resource routes or controllers (that's Story 0.2+)
- Do not add token revocation or rotation features
- Do not add scopes/abilities to tokens (keep it simple for Stage 1)

## Constraints

- Use Pest syntax for all tests (`test()` functions, `expect()` assertions) -- the project does not use PHPUnit class-based tests
- Tests go in `tests/Feature/` and use `RefreshDatabase` (already configured in `tests/Pest.php` for Feature tests)
- Follow the existing test pattern: create user via factory, use `actingAs()` or set auth headers, assert response
- The Artisan command should accept an email address and output the token to the console
- The command should go in `app/Console/Commands/` (this directory doesn't exist yet, create it)
- For auth tests, create a minimal test route (e.g., `GET /api/v1/health`) that returns JSON -- this is temporary scaffolding that Story 0.2 will build on

## Acceptance Criteria

- [ ] `php artisan api:create-token {email}` creates a token for the specified user and outputs it
- [ ] The command fails gracefully if the email doesn't match a user
- [ ] A test route `GET /api/v1/health` exists and returns `{"status": "ok"}` when authenticated
- [ ] Test: request to `/api/v1/health` with valid bearer token returns 200
- [ ] Test: request to `/api/v1/health` without a token returns 401
- [ ] Test: request to `/api/v1/health` with an invalid token returns 401
- [ ] All existing tests still pass (`php artisan test`)

---

## Tech Analysis

- **No `app/Console/Commands/` directory exists.** It needs to be created. Laravel 12 auto-discovers commands in this directory.
- **Test patterns from existing tests:** Tests use `$this->actingAs($user)` for session auth. For API token auth, tests should use `$this->withHeader('Authorization', 'Bearer '.$token)` or Sanctum's `Sanctum::actingAs()` test helper.
- **Pest is configured** in `tests/Pest.php` to extend `TestCase` and use `RefreshDatabase` for all Feature tests. No additional setup needed.
- **User factory exists** (`Database\Factories\UserFactory`) and is used throughout the test suite. Users belong to an Account -- factory likely handles this.
- **The test route** should be registered in `routes/api.php`. Since this file doesn't exist yet, this prompt needs to create a minimal version. Story 0.2 will expand it with the full routing structure. Register it in `bootstrap/app.php` via the `api:` parameter in `withRouting()`.

## References

- `platform/tests/Pest.php` -- Pest configuration, RefreshDatabase binding
- `platform/tests/Feature/Http/Controllers/StoreControllerTest.php` -- example of a comprehensive feature test with factories and assertions
- `platform/tests/Feature/Auth/AuthenticationTest.php` -- existing auth test patterns
- `platform/app/Models/User.php` -- User model with HasApiTokens (after Prompt 1)
- `platform/database/factories/UserFactory.php` -- how to create test users

## Files

- Create `platform/app/Console/Commands/CreateApiToken.php` -- Artisan command for token creation
- Create `platform/routes/api.php` -- minimal file with health check route (Story 0.2 will expand)
- Modify `platform/bootstrap/app.php` -- add `api:` parameter to `withRouting()` pointing to `routes/api.php`
- Create `platform/tests/Feature/Api/AuthenticationTest.php` -- Pest tests for token auth
