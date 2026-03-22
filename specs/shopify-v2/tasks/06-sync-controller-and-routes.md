# Task 06 — Sync Controller & Routes

## Starting Prompt

> I'm working through the Shopify v2 migration plan at `specs/shopify-v2/`. Please read `specs/shopify-v2/overview.md` and `specs/shopify-v2/tasks/06-sync-controller-and-routes.md`, then implement Task 06 in full. Work through the checklist at the bottom of the task file. Tasks 01–05 are already complete. Don't start Task 07.



## Goal

Expose the sync operations via HTTP endpoints on the Fibermade platform so the settings page UI can trigger them and poll for status.

## Routes to Add

All routes are under `/creator/` middleware (authenticated creator).

```
POST   /creator/shopify/sync/all           → trigger full sync
POST   /creator/shopify/sync/products      → trigger product sync only
POST   /creator/shopify/sync/collections   → trigger collection sync only
POST   /creator/shopify/sync/inventory     → trigger inventory sync only
GET    /creator/shopify/sync/status        → get current sync state
PATCH  /creator/shopify/settings           → save auto_sync toggle
```

## Controller: `ShopifySyncController`

### `triggerSync(Request $request, string $type)`

Or individual methods per sync type — follow whatever convention exists in the creator controllers.

**Logic:**
1. Get the authenticated user's Shopify integration (or 404 if none)
2. Check integration is active
3. Call the appropriate `ShopifySyncOrchestrator` method
4. Return `202 Accepted` with current sync state

**Response:**
```json
{
  "message": "Sync started",
  "sync": { "status": "running", "current_step": "products", ... }
}
```

### `status(Request $request)`

Returns the current sync state from `integration.settings.sync`.

```json
{
  "connected": true,
  "shop": "store.myshopify.com",
  "sync": { "status": "idle", "last_result": {...}, ... }
}
```

### `updateSettings(Request $request)`

Saves `auto_sync` (boolean) to `integration.settings.auto_sync`. Returns `200 OK`.

```json
{ "auto_sync": true }
```

## Notes

- These are web routes (session auth), not API routes — they're called from the Inertia settings page
- Return JSON responses (the UI will use `axios` or `router.visit()` with `only:`)
- Use Wayfinder for the frontend to reference these routes type-safely

## Files to Create

- `platform/app/Http/Controllers/Creator/ShopifySyncController.php`

## Files Likely Affected

- `platform/routes/creator.php` (add routes)

## Tests

- Test 202 returned when sync dispatched
- Test 409 when sync already running
- Test 404 when no Shopify integration exists
- Test status endpoint returns correct shape
- Test `updateSettings` saves auto_sync to integration settings

## Checklist

- [ ] Create `ShopifySyncController` — check sibling creator controllers for conventions
- [ ] Implement trigger methods (all, products, collections, inventory) — resolve integration, call orchestrator, return 202
- [ ] Implement `status()` — return integration sync state as JSON
- [ ] Return 409 when sync already running
- [ ] Return 404 when no active Shopify integration exists
- [ ] Register all routes in `creator.php`
- [ ] Implement `updateSettings()` — validate and save `auto_sync` boolean to integration settings
- [ ] Run `php artisan wayfinder:generate` so frontend gets typed route helpers
- [ ] Write tests for all paths listed above
- [ ] Run tests and confirm passing
