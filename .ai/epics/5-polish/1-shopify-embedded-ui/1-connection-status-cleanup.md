status: done

# Story 5.1: Prompt 1 -- Connection Status Page & Demo Cleanup

## Context

The Shopify embedded app has a working connection flow (connect, disconnect, reconnect) and a home page (`app._index.tsx`) that shows basic connection status: shop name, connection time, import progress, and disconnect button. However, the home page focuses heavily on the initial import flow and doesn't surface ongoing integration health. The `app.additional.tsx` route is a leftover demo/template page from the Shopify CLI scaffold. The navigation in `app.tsx` still links to this demo page and to the Push page (which is a developer tool, not a merchant feature). The Integration model on the platform has `active`, `settings` (JSON with shop domain), and timestamps. The `FibermadeClient` already has `getIntegration()` which returns `IntegrationData` including `active`, `settings`, `created_at`, and `updated_at`.

## Goal

Clean up the Shopify app by removing demo pages, updating navigation to reflect the final page structure, and enhancing the home page to show a proper connection status dashboard. The home page should show the linked Fibermade account status, Shopify store domain, integration active/inactive state, and integration last updated timestamp. This replaces the import-focused UI with a persistent connection overview while preserving the import flow for new connections.

## Non-Goals

- Do not build the sync history page -- that's Prompt 3
- Do not build the settings page -- that's Prompt 4
- Do not add manual sync triggers -- that's Prompt 2
- Do not modify any platform API endpoints
- Do not change the connect/disconnect flow logic

## Constraints

- Delete `shopify/app/routes/app.additional.tsx` entirely
- Update navigation in `app.tsx` to show: Home, Sync History, Settings (remove Additional and Push links)
- The Push route (`app.push.tsx`) should remain functional but not appear in navigation (it's a developer/API tool)
- The home page loader should fetch integration data from the platform API via `FibermadeClient.getIntegration()` to get current status
- Use existing app layout components (`s-section` or `s-card`, `s-paragraph`) for the connection status display; show integration status as plain text (e.g. "Active" / "Inactive")
- Place the connection status section at the top of the home page; import flow sections below it; existing "Connected to Fibermade" / disconnect block last
- When `getIntegration()` fails (and we are not in the reconnect-required path), still render the connection status section with shop and "Connected since," and show "—" or "Unknown" for integration status and "Integration last updated"
- Preserve the existing import flow UI (pending/in_progress/complete/failed states) — it appears below the connection status section
- The disconnect button and reconnect error handling should remain unchanged
- Follow existing patterns in the app (loader/action pattern, s-* components, FibermadeClient usage)

## Acceptance Criteria

- [ ] `app.additional.tsx` is deleted
- [ ] Navigation in `app.tsx` shows: Home, Sync History, Settings
- [ ] Push link removed from navigation (route still exists)
- [ ] Home page shows a connection status section at the top (above import flow) with:
  - Shopify store domain
  - Integration status (Active / Inactive), or "—" / "Unknown" when integration API call failed
  - Connected since date
  - Integration last updated (from integration `updated_at`), or "—" / "Unknown" when API call failed
- [ ] Home page loader fetches integration data via `getIntegration()`; on API failure (when not reconnect-required), still returns local data so the status section renders with fallbacks
- [ ] Import flow UI still works for new connections (pending → in_progress → complete)
- [ ] Disconnect button and reconnect error handling unchanged
- [ ] No broken links or navigation errors

---

## Tech Analysis

- **Loader changes**: The current `app._index.tsx` loader already calls `client.getIntegration(connection.fibermadeIntegrationId)`. Extend the returned payload: when the call succeeds, add `integrationActive: integration.active` and `integrationUpdatedAt: integration.updated_at` to the loader data. When it fails (and we are not in the reconnect-required path, i.e. we still return local connection data), omit these fields or set them to `undefined` so the UI can show "—" or "Unknown" for status and "Integration last updated." Extend the `ConnectionStatus` type with `integrationActive?: boolean` and `integrationUpdatedAt?: string`.
- **Navigation update**: `app.tsx` renders `<s-app-nav>` with `<s-link>` components. Update the links to: `/app` (Home), `/app/sync-history` (Sync History), `/app/settings` (Settings). The Sync History and Settings routes don't exist yet — the links will show as navigation items but lead to 404 until Prompts 3 and 4 create them. That's fine for incremental development.
- **Connection status section**: Use existing app components: `s-section` (or `s-card`) with heading "Connection status," `s-paragraph` for label/value rows. Show: Shopify store domain (from `connection.shop`), integration status as plain text "Active" or "Inactive" (from `integrationActive`; when undefined show "—" or "Unknown"), Connected since (from `connectedAt`), Integration last updated (from `integrationUpdatedAt`, formatted; when undefined show "—" or "Unknown"). Place this section at the top of the home page, above all import flow sections and above the "Connected to Fibermade" block.
- **Demo page deletion**: Simply delete `app.additional.tsx`. React Router won't register a route for a non-existent file.

## References

- `shopify/app/routes/app.tsx` -- navigation, layout, loader (checks connection)
- `shopify/app/routes/app._index.tsx` -- current home page with import flow
- `shopify/app/routes/app.additional.tsx` -- demo page to delete
- `shopify/app/routes/app.push.tsx` -- push route (keep, remove from nav)
- `shopify/app/services/fibermade-client.server.ts` -- `getIntegration()` method
- `shopify/app/services/fibermade-client.types.ts` -- `IntegrationData` type

## Files

- Delete `shopify/app/routes/app.additional.tsx`
- Modify `shopify/app/routes/app.tsx` -- update navigation links
- Modify `shopify/app/routes/app._index.tsx` — extend loader return with `integrationActive` and `integrationUpdatedAt`; extend `ConnectionStatus` type; add connection status section at top of page with fallbacks when integration data is missing
