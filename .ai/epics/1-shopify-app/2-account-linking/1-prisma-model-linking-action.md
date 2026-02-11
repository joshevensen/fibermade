status: done

# Story 1.2: Prompt 1 -- Prisma Model & Linking Server Action

## Context

Story 1.1 created the `FibermadeClient` service with full CRUD methods for all platform API resources, including `createIntegration()`, `healthCheck()`, and typed error handling. The Shopify app has no way to store per-shop Fibermade credentials or know which Integration record belongs to which shop. The `Session` model stores Shopify OAuth data (shop domain, access token) but has no Fibermade-related fields. After Shopify OAuth completes, the merchant lands on the app dashboard (`/app`) with no linking flow.

## Goal

Create a Prisma model to store the Fibermade connection state per shop, and build the server-side action that handles the account linking flow: verify credentials against the Fibermade API, create an Integration record, and persist the connection locally. After this prompt, the backend logic for linking is complete -- Prompt 2 adds the UI.

## Non-Goals

- Do not build the linking UI (that's Prompt 2)
- Do not modify existing Shopify OAuth or session handling
- Do not modify the platform API (no changes to the `platform/` directory)
- Do not add sync logic, product management, or any features beyond the connection
- Do not handle the uninstall webhook (that's Story 1.3)

## Constraints

- The Prisma model stores: `shop` (unique identifier), `fibermadeApiToken` (the Sanctum bearer token), `fibermadeIntegrationId` (the ID of the Integration record in the platform), and `connectedAt` timestamp
- The `shop` field should be unique -- one Fibermade connection per Shopify store
- The linking action is a React Router server action on a new route (`/app/connect`)
- Use the `FibermadeClient` from Story 1.1 for all API calls
- The action should:
  1. Accept the Fibermade API token from the merchant
  2. Call `healthCheck()` to verify the token is valid
  3. Get the Shopify session to extract the shop domain and access token
  4. Call `createIntegration()` to create an Integration record with type "shopify", the Shopify access token as credentials, and shop domain in settings
  5. Store the Fibermade API token and Integration ID in the local database
- Handle edge cases: invalid token (401 from health check), shop already linked (upsert or error), network failures, API errors
- The Shopify access token (from the Session) is what gets stored as `credentials` in the Integration record -- this is how the platform will call back to Shopify's API in future epics

## Acceptance Criteria

- [ ] New Prisma model `FibermadeConnection` with fields: `id`, `shop` (unique), `fibermadeApiToken`, `fibermadeIntegrationId` (Int), `connectedAt` (DateTime)
- [ ] Prisma migration creates the `FibermadeConnection` table
- [ ] `shopify/app/routes/app.connect.tsx` exists with an `action` function
- [ ] Action validates the submitted API token by calling `FibermadeClient.healthCheck()`
- [ ] On valid token: creates an Integration record via `FibermadeClient.createIntegration()` with `type: "shopify"`, `credentials` set to the Shopify access token, `settings: { shop: "<shop-domain>" }`, `active: true`
- [ ] On successful integration creation: stores `fibermadeApiToken`, `fibermadeIntegrationId`, and `connectedAt` in `FibermadeConnection`
- [ ] If shop is already linked: returns appropriate error (not a silent overwrite)
- [ ] If token is invalid (401/403): returns error indicating invalid credentials
- [ ] If API is unreachable: returns error indicating connection failure
- [ ] Action returns structured JSON with `{ success: true, integrationId }` or `{ success: false, error: string, field?: string }`

---

## Tech Analysis

- **Prisma migration**: Run `npx prisma migrate dev --name add_fibermade_connection` to create the migration. The existing migration infrastructure is in `prisma/migrations/`. SQLite is the dev database.
- **React Router action pattern**: The existing `app._index.tsx` shows the pattern -- export an `action` async function that receives `{ request }: ActionFunctionArgs`, authenticates with `authenticate.admin(request)`, does work, and returns data. The UI calls it via `useFetcher().submit()`.
- **Getting the Shopify session data**: `authenticate.admin(request)` returns `{ admin, session }`. The `session` object has `shop` (domain) and `accessToken` (Shopify API token). This access token is what we store as Integration credentials.
- **FibermadeClient instantiation**: Create the client with `new FibermadeClient({ baseUrl: process.env.FIBERMADE_API_URL })`, then call `client.setToken(submittedToken)` with the merchant's Fibermade API token before making calls.
- **Integration create payload**: `{ type: "shopify", credentials: session.accessToken, settings: { shop: session.shop }, active: true }`. The platform encrypts `credentials` on storage and never returns it in API responses.
- **Prisma upsert vs create**: Use `db.fibermadeConnection.findUnique({ where: { shop } })` first to check if already linked, and return an error if so. Don't silently overwrite -- the merchant should disconnect first (Story 1.3) if they want to re-link.
- **Environment variable**: `FIBERMADE_API_URL` needs to be available at runtime. In development, it can be set via the Shopify CLI config or a `.env` file. Add it to `env.d.ts` for TypeScript.
- **The route `/app/connect`** follows the file-based routing convention: `app/routes/app.connect.tsx`. Since it's nested under `app.*`, it inherits the app layout's admin authentication from `app.tsx`'s loader.

## References

- `shopify/prisma/schema.prisma` -- existing Session model, database configuration (SQLite)
- `shopify/app/db.server.ts` -- Prisma client singleton, import pattern
- `shopify/app/routes/app._index.tsx` -- action function pattern (authenticate.admin, return data)
- `shopify/app/routes/app.tsx` -- app layout loader that authenticates admin (all nested routes are authenticated)
- `shopify/app/shopify.server.ts` -- authenticate export, session access
- `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient class to use
- `shopify/app/services/fibermade-client.types.ts` -- type definitions
- `shopify/env.d.ts` -- TypeScript environment variable declarations
- `platform/app/Http/Requests/StoreIntegrationRequest.php` -- required fields: type, credentials, active

## Files

- Modify `shopify/prisma/schema.prisma` -- add FibermadeConnection model
- Create `shopify/prisma/migrations/<timestamp>_add_fibermade_connection/migration.sql` -- via `npx prisma migrate dev`
- Create `shopify/app/routes/app.connect.tsx` -- server action for linking flow (action only, no UI yet -- Prompt 2 adds the page component)
- Modify `shopify/env.d.ts` -- add `FIBERMADE_API_URL` to environment type declarations
