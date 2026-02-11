status: done

# Story 1.3: Prompt 2 -- Disconnect/Unlink Option

## Context

Prompt 1 extended the uninstall webhook to deactivate the Integration record and added connection status checking. The app layout loader returns `{ connected, connectionError? }` and the dashboard verifies the Integration is active. There's no way for a merchant to manually disconnect their Fibermade account without uninstalling the entire app. If a merchant wants to switch Fibermade accounts, re-link after a token rotation, or troubleshoot connection issues, they're stuck.

## Goal

Add a disconnect/unlink option that lets merchants manually sever the Fibermade connection from within the app. The disconnect action deactivates the Integration record on the platform and removes the local `FibermadeConnection`, returning the merchant to the initial linking flow. Also handle the case where the connection is in an error state (token revoked, integration inactive) -- show a clear message with options to reconnect or disconnect.

## Non-Goals

- Do not add a settings page beyond the disconnect option (that's Epic 5)
- Do not add token rotation or refresh logic
- Do not add re-linking without disconnecting first
- Do not add sync management or data export features
- Do not modify the platform API

## Constraints

- The disconnect action follows the same pattern as the connect action: React Router server action called via `useFetcher`
- Disconnect should deactivate (not delete) the Integration record: `PATCH /api/v1/integrations/{id}` with `{ active: false }`
- If the Fibermade API call fails during disconnect, still delete the local `FibermadeConnection` (the merchant wants to disconnect regardless)
- Show a confirmation before disconnecting -- use Shopify's modal or confirmation pattern
- After disconnecting, redirect to `/app/connect` (the linking page)
- The disconnected state and error states should be handled on the dashboard page (`/app`)

## Acceptance Criteria

- [ ] Dashboard page (`/app`) shows connection status with shop domain and a "Disconnect" option when connected
- [ ] Dashboard shows error state with reconnect/disconnect options when:
  - `connectionError: "integration_inactive"` -- "Your Fibermade integration has been deactivated"
  - `connectionError: "token_invalid"` -- "Your Fibermade API token is no longer valid"
- [ ] Clicking "Disconnect" shows a confirmation dialog (e.g., "Are you sure? This will remove the connection between your Shopify store and Fibermade account.")
- [ ] Confirming disconnect:
  1. Calls `FibermadeClient.updateIntegration(integrationId, { active: false })` (best-effort)
  2. Deletes the local `FibermadeConnection` record
  3. Shows success toast: "Disconnected from Fibermade"
  4. Redirects to `/app/connect`
- [ ] If the API call fails during disconnect: logs the error, still deletes local record, still shows success
- [ ] Error states show clear instructions on what to do (e.g., "Contact support" or "Reconnect with a new API token")
- [ ] The "Reconnect" option in error states navigates to `/app/connect` after cleaning up the stale local connection

---

## Tech Analysis

- **Disconnect action location:** Add a `disconnect` action to the dashboard route (`app._index.tsx`) that handles `intent: "disconnect"` in the form data. Alternatively, create a dedicated `app.disconnect.tsx` route. The dashboard route approach is simpler since the disconnect UI lives on the dashboard.
- **Action intent pattern:** The action can switch on `formData.get("intent")` to handle different operations. Example: `intent: "disconnect"` for the disconnect flow. This avoids creating a separate route file.
- **Confirmation dialog:** Shopify Polaris web components include `<s-modal>` for confirmation dialogs. Show the modal on button click, submit the disconnect action on confirm. If `<s-modal>` is not available in the web component version, use `window.confirm()` as a fallback or handle confirmation state in React.
- **Reconnect from error state:** When the connection is in an error state, the "Reconnect" button should: (1) delete the local `FibermadeConnection` record (via a `disconnect` action), then (2) redirect to `/app/connect`. This clears the stale local state so the linking flow starts fresh.
- **Dashboard layout when connected:** Show a simple card with:
  - "Connected to Fibermade" heading
  - Shop domain
  - Connection timestamp (`connectedAt`)
  - "Disconnect" button (secondary/destructive variant)
  - This is intentionally minimal -- Epic 5 adds the full settings/management UI
- **Dashboard layout for error states:** Show a banner at the top of the page with the error message and action buttons (Reconnect, Disconnect). Use `<s-banner tone="critical">` for errors.
- **Navigation cleanup:** After disconnect redirects to `/app/connect`, the linking flow from Story 1.2 kicks in. The `/app/connect` loader already redirects to `/app` if connected, and the `/app` loader returns `connected: false` which shows the link prompt. Make sure the redirect chain works cleanly.

## References

- `shopify/app/routes/app._index.tsx` -- dashboard page to extend with disconnect UI and action
- `shopify/app/routes/app.connect.tsx` -- linking page that merchants return to after disconnect
- `shopify/app/routes/app.tsx` -- layout loader provides `connected` and `connectionError` data
- `shopify/app/db.server.ts` -- Prisma client for deleting FibermadeConnection
- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient for deactivating Integration
- `shopify/app/shopify.server.ts` -- authenticate.admin for session access

## Files

- Modify `shopify/app/routes/app._index.tsx` -- add disconnect action (intent-based), connected state UI, error state UI with reconnect/disconnect options
