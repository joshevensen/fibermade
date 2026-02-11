status: done

# Story 1.3: Prompt 1 -- Uninstall Webhook & Connection Status Check

## Context

Story 1.2 created the `FibermadeConnection` Prisma model and the account linking flow. When a merchant links their Shopify store, the app creates an Integration record in the platform (via `POST /api/v1/integrations`) and stores the `fibermadeApiToken` and `fibermadeIntegrationId` locally. The existing `webhooks.app.uninstalled.tsx` handler only deletes Shopify sessions -- it doesn't touch the Fibermade Integration record. There's no connection status verification on app load -- if the Integration record is deactivated or deleted on the platform side, the app doesn't know.

## Goal

Extend the app uninstall webhook to deactivate the Integration record in the platform, and add a connection status check on app load that verifies the Integration record is still active. After this prompt, the app properly cleans up on uninstall and detects broken connections.

## Non-Goals

- Do not add a disconnect/unlink UI option (that's Prompt 2)
- Do not add re-linking or token refresh flows
- Do not modify the platform API
- Do not add new webhooks beyond extending the existing uninstall handler
- Do not add sync logic or product management features

## Constraints

- The uninstall webhook handler must be resilient -- if the Fibermade API call fails (token revoked, network error), the handler should still complete successfully (log the error, don't throw). Shopify does not retry webhooks on failure.
- The status check should happen in the `app.tsx` layout loader (runs on every authenticated page load) -- not in individual page loaders
- The status check should call `FibermadeClient.getIntegration(integrationId)` to verify the record exists and `active` is true
- If the status check fails (integration not found, not active, or token invalid), set a flag in the loader data that child routes can use to show a "disconnected" state
- Don't block page loads on the status check -- if the API is slow or down, the app should still render. Consider using a non-blocking approach or caching the status.
- Clean up the local `FibermadeConnection` record on uninstall (in addition to deactivating the platform Integration)

## Acceptance Criteria

- [ ] `webhooks.app.uninstalled.tsx` action:
  1. Loads the `FibermadeConnection` for the uninstalling shop
  2. If connection exists: calls `FibermadeClient.updateIntegration(integrationId, { active: false })` to deactivate the Integration record
  3. Deletes the local `FibermadeConnection` record
  4. Continues to delete Shopify sessions (existing behavior preserved)
  5. If the Fibermade API call fails: logs the error and continues (does not throw)
- [ ] `app.tsx` layout loader:
  1. After admin authentication, loads the `FibermadeConnection` for the current shop
  2. If a connection exists: calls `FibermadeClient.getIntegration(integrationId)` to verify it's active
  3. Returns `{ connected: boolean, connectionError?: string }` in loader data
  4. If no connection: returns `{ connected: false }`
  5. If connection exists but Integration is not active: returns `{ connected: false, connectionError: "integration_inactive" }`
  6. If connection exists but API call fails (401): returns `{ connected: false, connectionError: "token_invalid" }`
  7. If connection exists but API is unreachable: returns `{ connected: true }` (optimistic -- don't break the app because the API is temporarily down)
- [ ] Connection status is available to all child routes via `useLoaderData` or `useRouteLoaderData`
- [ ] Existing session deletion in uninstall webhook still works correctly

---

## Tech Analysis

- **Webhook handler has no session access.** The `authenticate.webhook(request)` returns `{ shop, session, topic }` but the session may be null (the webhook docs note sessions may already be deleted). The handler needs to query `FibermadeConnection` by `shop` directly, not through the session.
- **FibermadeClient in the webhook context:** The webhook handler doesn't have an admin session. To call the Fibermade API, it needs the `fibermadeApiToken` from the `FibermadeConnection` record. Instantiate a new `FibermadeClient`, set the token from the local record, and call `updateIntegration`.
- **Deactivation vs deletion:** The story says "deactivate" -- use `PATCH /api/v1/integrations/{id}` with `{ active: false }`, not DELETE. This preserves the Integration record and its logs for audit purposes.
- **Status check performance:** The `app.tsx` loader runs on every page navigation. Calling the Fibermade API on every load is expensive. Options:
  1. **Simple approach (recommended for now):** Make the API call but don't block rendering on it. Return `connected: true` optimistically if a `FibermadeConnection` record exists, and verify in the background or only on the connect/dashboard page.
  2. **Cache approach:** Check the API periodically (e.g., once per session or every 5 minutes) and cache the result.
  For this prompt, use the simple approach: check for a local `FibermadeConnection` record in the layout loader (fast, local DB query). Do the full API verification only on the dashboard page (`app._index.tsx` loader), not on every page load.
- **Error handling in the webhook:** Wrap the Fibermade API call in try/catch. Log errors with `console.error` (Shopify CLI shows these in the dev console). Do not re-throw -- return `new Response()` regardless.
- **Order of operations in uninstall:** The current handler checks `if (session)` before deleting sessions. The Fibermade cleanup should happen first (before session deletion), since it doesn't depend on the session. Query `FibermadeConnection` by shop, do the API call, delete the connection, then delete sessions.
- **`useRouteLoaderData`**: Child routes can access the parent layout's loader data via `useRouteLoaderData("routes/app")` (the route ID). This is how the dashboard page can know the connection status without its own API call.

## References

- `shopify/app/routes/webhooks.app.uninstalled.tsx` -- existing uninstall handler to extend
- `shopify/app/routes/app.tsx` -- app layout loader to extend with connection status
- `shopify/app/routes/app._index.tsx` -- dashboard page that shows connection state (from Story 1.2)
- `shopify/app/db.server.ts` -- Prisma client for querying/deleting FibermadeConnection
- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient for API calls
- `shopify/prisma/schema.prisma` -- FibermadeConnection model (from Story 1.2)
- `platform/app/Http/Requests/UpdateIntegrationRequest.php` -- update payload (active: boolean is `sometimes` validated)

## Files

- Modify `shopify/app/routes/webhooks.app.uninstalled.tsx` -- add Fibermade Integration deactivation and FibermadeConnection cleanup
- Modify `shopify/app/routes/app.tsx` -- add FibermadeConnection lookup to loader, return connection status
- Modify `shopify/app/routes/app._index.tsx` -- update to use connection status from layout loader data, do full API verification on dashboard load
