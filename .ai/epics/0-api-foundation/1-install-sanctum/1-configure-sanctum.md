status: done

# Story 0.1: Prompt 1 -- Install & Configure Sanctum

## Context

The platform is a Laravel 12 application using Fortify for session-based authentication. There is no API layer -- no `routes/api.php`, no API middleware, no token-based auth. The app uses `bootstrap/app.php` for configuration (not Kernel.php). Routes are registered via `withRouting()` in `bootstrap/app.php`, with `routes/creator.php` and `routes/store.php` included via `require` statements in `routes/web.php`.

## Goal

Install Laravel Sanctum and configure the application for token-based API authentication. After this prompt, the app has Sanctum installed, the User model can issue API tokens, and the API middleware stack is configured -- but no API routes or controllers exist yet.

## Non-Goals

- Do not create `routes/api.php` or any API routes (that's Prompt 2 and Story 0.2)
- Do not create any API controllers or resources
- Do not modify existing web routes, controllers, or Inertia pages
- Do not build a token management UI
- Do not change how Fortify or session-based auth works

## Constraints

- Laravel 12 uses `bootstrap/app.php` for all configuration -- do not create a Kernel.php
- Sanctum should be configured for stateless token auth only (no SPA/cookie auth needed since the Inertia app uses session auth via Fortify)
- The `api` guard should use Sanctum's token driver, not replace the existing `web` guard
- Follow existing code style: the User model uses traits on line 16 (`use HasFactory, Notifiable, SoftDeletes;`)

## Acceptance Criteria

- [ ] `composer show laravel/sanctum` confirms Sanctum is installed
- [ ] `config/sanctum.php` exists with default configuration
- [ ] `personal_access_tokens` migration exists and can run successfully
- [ ] User model has the `HasApiTokens` trait
- [ ] `config/auth.php` has a `sanctum` guard configured
- [ ] `bootstrap/app.php` registers API middleware with Sanctum's stateless guard
- [ ] Existing web auth and Inertia pages still work (no regressions)

---

## Tech Analysis

- **Sanctum is not in composer.json.** Needs `composer require laravel/sanctum`.
- **bootstrap/app.php** currently only configures `web:` routing in `withRouting()`. The `api:` parameter needs to be added (even though routes/api.php doesn't exist yet, the middleware stack needs to be ready). Alternatively, this can wait for Story 0.2 when routes/api.php is created -- but the `withMiddleware` section should configure API middleware now.
- **config/auth.php** has only a `web` guard using the `session` driver. A `sanctum` guard needs to be added. Note the custom `registration_email_whitelist` config -- don't remove it.
- **User model** uses `HasFactory, Notifiable, SoftDeletes` traits. Add `HasApiTokens` to this list.
- **No app/Console/Commands/ directory exists.** This is relevant for Prompt 2 (token creation command).
- **Laravel 12 Sanctum integration**: In Laravel 12, Sanctum's middleware is typically configured via `$middleware->statefulApi()` or by adding the Sanctum guard to the `api` middleware group in `bootstrap/app.php`.

## References

- `platform/bootstrap/app.php` -- current routing and middleware registration, need to add API middleware
- `platform/config/auth.php` -- current guards (web only), need to add sanctum guard
- `platform/app/Models/User.php` -- need to add HasApiTokens trait
- `platform/composer.json` -- confirm Sanctum not installed, verify Laravel version (12)

## Files

- Modify `platform/composer.json` -- Sanctum added via `composer require`
- Modify `platform/app/Models/User.php` -- add `HasApiTokens` trait
- Modify `platform/config/auth.php` -- add `sanctum` guard
- Modify `platform/bootstrap/app.php` -- configure API middleware with Sanctum
- Create `platform/config/sanctum.php` -- via `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
- Create `platform/database/migrations/xxxx_create_personal_access_tokens_table.php` -- via Sanctum publish or migration
