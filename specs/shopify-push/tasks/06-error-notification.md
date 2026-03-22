# Task 06 — Error Notification (Sentry + User Banner)

## Starting Prompt

> I'm working through the Shopify push spec at `specs/shopify-push/`. Please read `specs/shopify-push/overview.md` and `specs/shopify-push/tasks/06-error-notification.md`, then implement this task. Work through the checklist before marking done.

---

## Goal

1. Report all Shopify sync failures to Sentry — both hard job failures and soft API errors that are caught and logged.
2. Show a persistent banner to the creator when sync errors exist, directing them to contact support.

---

## Current State

- Sentry is installed and working in the platform. Hard job failures (unhandled exceptions) already reach Sentry automatically.
- Soft failures (`ShopifyApiException` caught inside jobs, logged to IntegrationLog as `Error`) are silent — Sentry never sees them.
- There is no proactive user-facing notification for sync errors.
- `IntegrationLog` has a `status` field with values `Success`, `Warning`, `Error`.

---

## Part A: Sentry — Soft Failure Reporting

### Report caught exceptions in all push jobs

Every push job has a try/catch that logs to IntegrationLog and swallows the exception. Add a `Sentry::captureException()` call before swallowing so soft failures also reach Sentry.

Pattern to apply across all push jobs:

```php
} catch (ShopifyApiException $e) {
    \Sentry\captureException($e);

    IntegrationLog::create([
        'integration_id'  => $integration->id,
        'loggable_type'   => Colorway::class,
        'loggable_id'     => $this->colorway->id,
        'status'          => IntegrationLogStatus::Error,
        'message'         => 'Failed to push to Shopify: ' . $e->getMessage(),
        'metadata'        => ['operation' => 'product_update', 'error' => $e->getMessage()],
        'synced_at'       => now(),
    ]);
}
```

Apply this pattern to:
- `SyncColorwayCatalogToShopifyJob`
- `SyncColorwayImagesToShopifyJob`
- `SyncBaseToShopifyJob`
- `SyncBaseDeletedToShopifyJob`
- `SyncCollectionToShopifyJob` (Task 03)
- `SyncCollectionDeletedToShopifyJob` (Task 03)
- `SyncInventoryToShopifyJob` (Task 04)

### Add `failed()` to all push jobs for hard failures

When a job exhausts all retries and dies, Laravel calls `failed(Throwable $exception)` on the job. Use this to ensure Sentry captures the final failure with context.

```php
public function failed(Throwable $exception): void
{
    \Sentry\withScope(function (\Sentry\State\Scope $scope) use ($exception): void {
        $scope->setContext('shopify_sync', [
            'job'        => static::class,
            'colorway'   => $this->colorway->id ?? null,
            'account'    => $this->colorway->account_id ?? null,
        ]);

        \Sentry\captureException($exception);
    });
}
```

Add `failed()` to all push jobs. Adjust the context fields to match the job's constructor properties.

Sentry is already capturing hard failures automatically via its Laravel integration, but adding `failed()` with context (job class, entity ID, account ID) makes the Sentry alerts actionable rather than generic.

---

## Part B: Integration Error Flag

To power the user-facing banner without a database query on every page load, store an error flag on the integration.

### New Integration setting: `has_sync_errors`

When a push job logs an `Error` to IntegrationLog, also set `has_sync_errors = true` in `integration.settings`.

When a push job logs a `Success` for the same entity (error resolved on retry), clear the flag — but only if there are no other unacknowledged errors. Simplest approach: don't auto-clear. Instead, provide a way for the user to dismiss the banner (which clears the flag). Errors will be obvious from context.

#### Set the flag on error

Add a helper to `Integration`:

```php
public function flagSyncError(): void
{
    $settings = $this->settings ?? [];
    $settings['has_sync_errors'] = true;
    $this->update(['settings' => $settings]);
}

public function clearSyncErrors(): void
{
    $settings = $this->settings ?? [];
    $settings['has_sync_errors'] = false;
    $this->update(['settings' => $settings]);
}
```

Call `$integration->flagSyncError()` in every catch block, after `Sentry::captureException()`.

#### Clear the flag

Add a route and controller action for the user to dismiss the banner:

```
POST /creator/shopify/{integration}/errors/dismiss
```

```php
public function dismissErrors(Integration $integration): RedirectResponse
{
    $this->authorize('update', $integration);
    $integration->clearSyncErrors();

    return back();
}
```

The `{integration}` route segment enables Laravel route model binding. The integration ID should be passed from the shared Inertia data (already available in the layout as part of the `shopify` prop — extend it to include `integration_id`).

---

## Part C: User Banner

### What to show

A persistent red/orange banner at the top of every creator page (inside the creator layout) when the active Shopify integration has `has_sync_errors = true`.

Banner text:
> **Shopify sync error** — One or more updates failed to sync to your Shopify store. Please contact [support@fibermade.app](mailto:support@fibermade.app) for help.

Include a dismiss button (×) that posts to `/creator/shopify/errors/dismiss`. The banner should not reappear until the next error occurs.

### Implementation

The creator layout (`resources/js/layouts/CreatorLayout.vue`) already receives props from the backend. Pass the error flag through the Inertia shared data.

#### In `HandleInertiaRequests` middleware (or equivalent)

```php
public function share(Request $request): array
{
    $integration = null;
    $hasSyncErrors = false;

    if ($request->user()?->account) {
        $integration = Integration::where('account_id', $request->user()->account->id)
            ->where('type', 'shopify')
            ->first();

        $hasSyncErrors = (bool) ($integration?->settings['has_sync_errors'] ?? false);
    }

    return array_merge(parent::share($request), [
        'shopify' => [
            'has_sync_errors'  => $hasSyncErrors,
            'integration_id'   => $integration?->id,
        ],
    ]);
}
```

#### In `CreatorLayout.vue`

```vue
<script setup>
const { shopify } = usePage().props
</script>

<template>
  <div v-if="shopify.has_sync_errors" class="bg-red-50 border-b border-red-200 px-4 py-3 flex items-center justify-between">
    <p class="text-sm text-red-800">
      <strong>Shopify sync error</strong> — One or more updates failed to sync to your Shopify store.
      Please contact
      <a href="mailto:support@fibermade.app" class="underline">support@fibermade.app</a>
      for help.
    </p>
    <form method="POST" :action="route('creator.shopify.errors.dismiss', { integration: shopify.integration_id })">
      <button type="submit" class="text-red-500 hover:text-red-700 ml-4">×</button>
    </form>
  </div>

  <!-- rest of layout -->
</template>
```

Use whatever styling conventions the rest of the creator layout uses (Tailwind classes above are illustrative).

---

## Files to Touch

| File | Change |
|------|--------|
| All push jobs (Tasks 01–04) | Add `Sentry::captureException()` in catch blocks + `failed()` method |
| `app/Models/Integration.php` | Add `flagSyncError()` and `clearSyncErrors()` helpers |
| `app/Http/Middleware/HandleInertiaRequests.php` | Share `shopify.has_sync_errors` in shared data |
| `app/Http/Controllers/Creator/ShopifySyncController.php` | Add `dismissErrors()` action |
| `routes/creator.php` | Add `POST /shopify/{integration}/errors/dismiss` route |
| `resources/js/layouts/CreatorLayout.vue` | Add conditional banner |

---

## Tests

- `Integration::flagSyncError()` sets `has_sync_errors = true` in settings
- `Integration::clearSyncErrors()` sets `has_sync_errors = false`
- Each push job calls `Sentry::captureException()` on `ShopifyApiException`
- Each push job calls `integration->flagSyncError()` on error
- Each push job has a `failed()` method that calls `Sentry::captureException()` with context
- `dismissErrors()` calls `clearSyncErrors()` and redirects
- `HandleInertiaRequests` shares `has_sync_errors = true` when flag is set
- `HandleInertiaRequests` shares `has_sync_errors = false` when no integration or flag not set

---

## Checklist

- [ ] Add `flagSyncError()` and `clearSyncErrors()` to `Integration` model
- [ ] Update all push job catch blocks — add `Sentry::captureException()` + `flagSyncError()`
- [ ] Add `failed()` method to all push jobs
- [ ] Add `shopify.has_sync_errors` to `HandleInertiaRequests::share()`
- [ ] Add `dismissErrors()` to `ShopifySyncController`
- [ ] Add `POST /creator/shopify/{integration}/errors/dismiss` route
- [ ] Add banner to `CreatorLayout.vue`
- [ ] Write all tests
- [ ] Run `php artisan test --compact` — all passing
- [ ] Run `vendor/bin/pint --dirty`
