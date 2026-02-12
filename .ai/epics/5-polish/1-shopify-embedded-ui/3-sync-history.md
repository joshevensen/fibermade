status: pending

# Story 5.1: Prompt 3 -- Sync History Page

## Context

The `IntegrationLog` model on the platform stores sync operation records with `status` (success/error/warning), `message`, `loggable_type` (the resource type that was synced, e.g., "App\\Models\\Colorway"), `loggable_id`, `metadata` (JSON), and `synced_at` timestamp. The platform API exposes `GET /api/v1/integrations/{integration}/logs` which returns logs newest-first with a configurable `limit` parameter (default 50, max 100). The `FibermadeClient` in the Shopify app already has `getIntegrationLogs(integrationId, params?)` that calls this endpoint and returns `PaginatedResponse<IntegrationLogData>`. The Shopify app navigation (updated in Prompt 1) includes a "Sync History" link pointing to `/app/sync-history`, but the route doesn't exist yet.

## Goal

Create a sync history page at `/app/sync-history` that surfaces IntegrationLog data in a filterable, readable table. Merchants can see recent sync operations, filter by status (success/error/warning), and understand what's been synced and what failed.

## Non-Goals

- Do not add pagination controls (the API returns up to 100 logs, which is sufficient for now)
- Do not add log detail views or drill-down to individual resources
- Do not modify the platform API or IntegrationLog model
- Do not add real-time updates or polling
- Do not add date range filtering

## Constraints

- Create a new route file `shopify/app/routes/app.sync-history.tsx` (file-based routing: `/app/sync-history`)
- The loader fetches logs via `FibermadeClient.getIntegrationLogs()` with `limit: 100`
- If not connected, redirect to `/app`
- Use Polaris `IndexTable` or `DataTable` for the log list
- Display columns: Status (badge), Resource Type, Message, Synced At
- Status badges: success = "success" tone, error = "critical" tone, warning = "warning" tone
- Resource type should be human-readable: strip the `App\\Models\\` prefix (e.g., "Colorway", "Collection", "Order")
- Format `synced_at` as a relative time (e.g., "2 minutes ago") or readable date
- Add a status filter using Polaris `Filters` or `ChoiceList` -- filter client-side since we load all logs at once
- Show an empty state if no logs exist (Polaris `EmptyState` component)

## Acceptance Criteria

- [ ] Route `app.sync-history.tsx` exists and renders at `/app/sync-history`
- [ ] Loader fetches integration logs via `getIntegrationLogs()` with limit 100
- [ ] Redirects to `/app` if shop is not connected
- [ ] Table displays: Status (badge), Resource Type, Message, Synced At
- [ ] Status badges use appropriate tones (success/critical/warning)
- [ ] Resource type displayed without namespace prefix (e.g., "Colorway" not "App\\Models\\Colorway")
- [ ] Synced at displayed as readable date/time
- [ ] Client-side status filter (all / success / error / warning)
- [ ] Empty state shown when no logs exist
- [ ] Page title is "Sync History"
- [ ] Navigation highlights "Sync History" when on this page

---

## Tech Analysis

- **Route file naming**: `app.sync-history.tsx` maps to `/app/sync-history` via React Router's file-based routing. The dot in the filename creates a nested route under the `app` layout, inheriting authentication.
- **Loader pattern**: Follow the same pattern as `app._index.tsx` -- authenticate via `authenticate.admin(request)`, look up `FibermadeConnection` by shop, redirect if not found, create `FibermadeClient`, fetch data.
- **IntegrationLogData type**: Already defined in `fibermade-client.types.ts`:
  ```typescript
  interface IntegrationLogData {
    id: number;
    integration_id: number;
    loggable_type: string | null;
    loggable_id: number | null;
    status: string;
    message: string;
    metadata: Record<string, unknown> | null;
    synced_at: string | null;
    created_at: string;
    updated_at: string;
  }
  ```
- **Resource type parsing**: `loggable_type` comes as `"App\\Models\\Colorway"`. Split on `\\` and take the last segment. Handle null (show "—" or "General").
- **Client-side filtering**: Load all logs, store in state, filter using `useState` for the selected status filter. Polaris `Filters` component with a `ChoiceList` for status.
- **Date formatting**: Use JavaScript `Intl.DateTimeFormat` or a simple helper for relative time. Shopify Polaris doesn't include a date formatter -- keep it simple with `new Date(synced_at).toLocaleString()`.
- **IndexTable vs DataTable**: `IndexTable` is better for selectable rows (not needed here). Use `DataTable` for a simple read-only table, or a custom card-based layout with Polaris `ResourceList`. `DataTable` is simplest.

## References

- `shopify/app/routes/app._index.tsx` -- loader pattern (auth, connection check, client setup)
- `shopify/app/services/fibermade-client.server.ts` -- `getIntegrationLogs()` method
- `shopify/app/services/fibermade-client.types.ts` -- `IntegrationLogData` interface
- `platform/app/Http/Controllers/Api/V1/IntegrationLogController.php` -- API endpoint behavior (limit, ordering)
- `platform/app/Models/IntegrationLog.php` -- model fields and relationships

## Files

- Create `shopify/app/routes/app.sync-history.tsx` -- new page with loader, status filter, and log table
