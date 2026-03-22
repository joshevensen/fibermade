# Task 02 — ShopifyConnectionController & Routes

## Starting Prompt

> I'm working through the Shopify connect token migration at `specs/shopify-connect-token/`. Please read `specs/shopify-connect-token/overview.md` and `specs/shopify-connect-token/tasks/02-controller-and-routes.md`, then implement Task 02 in full. Work through the checklist at the bottom of the task file. Task 01 is already complete. This task touches the Laravel platform only (`platform/` directory).

## Goal

Create `ShopifyConnectionController` with three public (no Sanctum auth) endpoints that replace the current Sanctum-token-based integration flow. Also delete the old `storeApiToken` endpoint.

## New Controller

`app/Http/Controllers/Api/V1/ShopifyConnectionController.php`

### `connect` — `POST /api/v1/shopify/connect`

```php
public function connect(Request $request): JsonResponse
```

Validation:
```php
$validated = $request->validate([
    'connect_token' => ['required', 'string'],
    'shop'          => ['required', 'string'],
    'shopify_access_token' => ['required', 'string'],
]);
```

Logic:
1. Find `Account` by `shopify_connect_token` — return `422` with `"Invalid connect token"` if not found (don't leak whether the account exists)
2. Soft-delete any existing Shopify integrations for the account (re-use the same pattern as `IntegrationController::store` — collect old IDs, re-associate `ExternalIdentifier` rows, then delete)
3. Create new `Integration`:
   - `type` → `IntegrationType::Shopify`
   - `credentials` → `$validated['shopify_access_token']` (encrypted by model cast)
   - `settings` → `['shop' => $validated['shop']]`
   - `active` → `true`
4. Return `201` with `{ "data": { "integration_id": $integration->id } }`

### `disconnect` — `POST /api/v1/shopify/disconnect`

```php
public function disconnect(Request $request): JsonResponse
```

Validation:
```php
$validated = $request->validate([
    'connect_token' => ['required', 'string'],
    'shop'          => ['required', 'string'],
]);
```

Logic:
1. Find `Account` by `connect_token` — if not found, return `204` silently (don't error on unknown tokens)
2. Find active Shopify integration matching the shop domain via `Integration::findShopifyByShopDomain()` scoped to the account
3. Set `active = false`
4. Return `204`

### `status` — `GET /api/v1/shopify/status`

```php
public function status(Request $request): JsonResponse
```

Validation:
```php
$request->validate([
    'connect_token' => ['required', 'string'],
    'shop'          => ['required', 'string'],
]);
```

Logic:
1. Find `Account` by `connect_token` — if not found, return `{ "data": { "active": false } }`
2. Find Shopify integration for account matching shop domain
3. Return `{ "data": { "active": bool, "integration_id": int|null } }`

## Routes

In `routes/api.php`, add a group with `throttle:10,1` for `connect` and no throttle for the others:

```php
Route::post('shopify/connect', [ShopifyConnectionController::class, 'connect'])
    ->middleware('throttle:10,1');
Route::post('shopify/disconnect', [ShopifyConnectionController::class, 'disconnect']);
Route::get('shopify/status', [ShopifyConnectionController::class, 'status']);
```

These routes must be **outside** the Sanctum `auth:sanctum` middleware group.

## Cleanup — Delete Old Token Endpoint

- Delete `UserController::storeApiToken()` method
- Delete the route `POST /creator/settings/api-token` from `routes/creator.php` (or wherever it lives)
- Revoke all existing Sanctum tokens named `'shopify'`:
  ```php
  \Laravel\Sanctum\PersonalAccessToken::where('name', 'shopify')->delete();
  ```
  Run this as a one-time database migration or via tinker.

## Tests

Test file: `tests/Feature/Api/V1/ShopifyConnectionControllerTest.php`

- `connect` with valid token creates integration and returns `integration_id`
- `connect` with invalid token returns `422`
- `connect` replaces existing integration (re-associates external identifiers)
- `connect` is rate limited (mock or skip in unit tests)
- `disconnect` with valid token + shop sets integration to inactive
- `disconnect` with unknown token returns `204` silently
- `status` returns `active: true` when integration exists and is active
- `status` returns `active: false` when integration is inactive or not found
- `status` returns `active: false` when connect token is unknown

## Checklist

- [ ] Create `ShopifyConnectionController` with `connect`, `disconnect`, `status` methods
- [ ] Add routes to `routes/api.php` outside the auth middleware group (with throttle on `connect`)
- [ ] Run `php artisan wayfinder:generate` after adding routes
- [ ] Delete `UserController::storeApiToken()`
- [ ] Delete `POST /creator/settings/api-token` route
- [ ] Revoke all Sanctum tokens named `'shopify'` (migration or tinker)
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
- [ ] Run `vendor/bin/pint --dirty`
