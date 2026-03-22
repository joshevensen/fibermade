# Task 04 — Shopify App Updates

## Starting Prompt

> I'm working through the Shopify connect token migration at `specs/shopify-connect-token/`. Please read `specs/shopify-connect-token/overview.md` and `specs/shopify-connect-token/tasks/04-shopify-app.md`, then implement Task 04 in full. Work through the checklist at the bottom of the task file. Tasks 01–03 are already complete and live in production. This task touches the Shopify app only (`shopify/` directory).

## Goal

Update the Shopify app to use the new connect token endpoints instead of Sanctum Bearer auth. Update the Prisma schema to replace `fibermadeApiToken` with `connectToken`. Update the UI label from "Fibermade API token" to "Fibermade Connect Token".

## Prisma Schema

`FibermadeConnection` — replace `fibermadeApiToken` with `connectToken`:

```prisma
model FibermadeConnection {
  id                     Int      @id @default(autoincrement())
  shop                   String   @unique
  connectToken           String
  fibermadeIntegrationId Int
  connectedAt            DateTime @default(now())
}
```

`connectToken` is the UUID from the Fibermade account — not a secret, no field encryption needed.

Reset migrations and create a fresh initial migration (schema is simple enough — two models, no existing data worth preserving in dev/staging).

## `app._index.tsx`

### `fibermadeRequest` helper

Remove the `token` parameter and `Authorization` header. The new endpoints don't use Bearer auth.

```typescript
async function fibermadeRequest(
  baseUrl: string,
  path: string,
  options: { method?: string; body?: unknown } = {}
): Promise<{ ok: boolean; status: number; data: unknown }>
```

### Loader

Replace the `GET /api/v1/integrations/{id}` call with `GET /api/v1/shopify/status`:

```typescript
const result = await fibermadeRequest(
  baseUrl,
  `/api/v1/shopify/status?connect_token=${connection.connectToken}&shop=${session.shop}`
);

// result.data: { data: { active: bool, integration_id: int|null } }
const status = (result.data as { data?: { active?: boolean } } | null)?.data;
if (status?.active === false) {
  return { connected: false, connectionError: "integration_inactive", ... };
}
```

### Connect action

Replace the health check + integration create calls with a single `POST /api/v1/shopify/connect`:

```typescript
const connectResult = await fibermadeRequest(baseUrl, "/api/v1/shopify/connect", {
  method: "POST",
  body: {
    connect_token: connectToken.trim(),
    shop: session.shop,
    shopify_access_token: shopifyAccessToken,
  },
});

if (!connectResult.ok) {
  // 422 → invalid token
  // other → generic error
}

const integrationId = (connectResult.data as { data?: { integration_id?: number } } | null)?.data?.integration_id;
```

Store `connectToken` in `FibermadeConnection` instead of `fibermadeApiToken`:

```typescript
await db.fibermadeConnection.create({
  data: {
    shop: session.shop,
    connectToken: connectToken.trim(),
    fibermadeIntegrationId: integrationId,
    connectedAt: new Date(),
  },
});
```

### Disconnect action

Replace `PATCH /api/v1/integrations/{id}` with `POST /api/v1/shopify/disconnect`:

```typescript
await fibermadeRequest(baseUrl, "/api/v1/shopify/disconnect", {
  method: "POST",
  body: {
    connect_token: connection.connectToken,
    shop: connection.shop,
  },
});
```

### UI label

Change the field label and helper text:

- Label: `"Fibermade Connect Token"` (was `"Fibermade API token"`)
- Helper: `"Find this in Fibermade → Settings → Shopify API"` (brief, scannable)
- Error messages: update any reference to "API token" → "connect token"

The `connectionError` state currently shows "API token no longer valid" — update to "Connect token no longer valid" and update the reconnect instruction to match the new flow.

## `webhooks.app.uninstalled.tsx`

Replace the `PATCH /api/v1/integrations/{id}` call with `POST /api/v1/shopify/disconnect`:

```typescript
await fetch(`${fibermadeApiUrl}/api/v1/shopify/disconnect`, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    connect_token: connection.connectToken,
    shop: connection.shop,
  }),
});
```

Remove `Authorization` header entirely.

## Checklist

**Prisma:**
- [ ] Update `FibermadeConnection` — replace `fibermadeApiToken` with `connectToken` (no encryption)
- [ ] Reset migrations and create fresh initial migration
- [ ] Run `npm run setup` to apply

**`app._index.tsx`:**
- [ ] Remove `token` param and `Authorization` header from `fibermadeRequest`
- [ ] Loader: replace integration status check with `GET /api/v1/shopify/status`
- [ ] Connect action: replace health check + integration create with `POST /api/v1/shopify/connect`
- [ ] Connect action: store `connectToken` instead of `fibermadeApiToken`
- [ ] Disconnect action: replace PATCH with `POST /api/v1/shopify/disconnect`
- [ ] UI: update field label, helper text, and error messages to say "connect token"

**`webhooks.app.uninstalled.tsx`:**
- [ ] Replace PATCH call with `POST /api/v1/shopify/disconnect`
- [ ] Remove `Authorization` header

**Verification:**
- [ ] Update/fix any tests that reference `fibermadeApiToken` or the old endpoints
- [ ] Run `npm run test:run` and confirm passing
- [ ] Deploy to staging and verify end-to-end: connect → status shows connected → disconnect → status shows disconnected → uninstall cleans up
