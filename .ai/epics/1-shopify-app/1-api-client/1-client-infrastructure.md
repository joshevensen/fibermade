status: done

# Story 1.1: Prompt 1 -- FibermadeClient Core Infrastructure

## Context

The Shopify app (`shopify/` directory) is a React Router template with Shopify OAuth, Prisma session storage (SQLite), webhook handlers, and an embedded app shell. It has no connection to the Fibermade platform -- no HTTP client, no API integration, and no custom services beyond Shopify's built-in `authenticate.admin()`. The platform API (Epic 0) provides versioned endpoints at `/api/v1/` with Sanctum bearer token auth, JSON responses, rate limiting (`X-RateLimit-*` headers), and consistent error formatting (422 validation errors with field-level details, 401/403/404/500).

## Goal

Create the core `FibermadeClient` service class in the Shopify app -- a typed TypeScript HTTP client that authenticates with Sanctum bearer tokens, handles errors consistently, and respects rate limiting. This prompt builds the infrastructure layer (HTTP methods, auth, error types, rate limiting). Prompt 2 adds the resource-specific CRUD methods.

## Non-Goals

- Do not add resource-specific methods (integrations, colorways, etc.) -- that's Prompt 2
- Do not create the Prisma model for storing API tokens -- that's Story 1.2
- Do not build any UI components or routes
- Do not modify existing Shopify app routes or webhook handlers
- Do not add the `FIBERMADE_API_URL` or `FIBERMADE_API_TOKEN` to any `.env` file (those are runtime config)

## Constraints

- The client is a server-side only module -- file should use the `.server.ts` convention (e.g., `app/services/fibermade-client.server.ts`) so it's never bundled for the browser
- Use the native `fetch` API (available in Node 20+, which this app requires) -- do not add axios or other HTTP libraries
- Bearer token is passed per-request or set on the client instance (different shops have different tokens)
- Base URL comes from `FIBERMADE_API_URL` environment variable
- All responses should be typed -- create TypeScript interfaces for the API response envelope and error shapes
- Error types should distinguish between: network errors, authentication errors (401), authorization errors (403), not found (404), validation errors (422 with field-level details), rate limited (429), and server errors (500)
- Rate limiting: read `X-RateLimit-Remaining` and `X-RateLimit-Reset` headers. When receiving a 429 response, implement exponential backoff with a maximum of 3 retries
- Follow the app's existing code style: ESM imports, TypeScript strict mode, no semicolons if that's the project convention (check existing files)

## Acceptance Criteria

- [ ] `app/services/fibermade-client.server.ts` exists with a `FibermadeClient` class
- [ ] Client accepts `baseUrl` and optional `token` in constructor
- [ ] `setToken(token: string)` method allows setting/changing the bearer token after construction
- [ ] Private methods for HTTP verbs: `get<T>`, `post<T>`, `patch<T>`, `delete` that handle auth headers, JSON serialization, and response parsing
- [ ] All requests include `Authorization: Bearer {token}`, `Content-Type: application/json`, and `Accept: application/json` headers
- [ ] `app/services/fibermade-client.types.ts` exists with TypeScript interfaces for: `ApiResponse<T>`, `PaginatedResponse<T>`, `ApiError`, `ValidationError`, `RateLimitInfo`
- [ ] Error handling maps HTTP status codes to typed error classes: `FibermadeApiError`, `FibermadeAuthError`, `FibermadeValidationError`, `FibermadeRateLimitError`, `FibermadeNotFoundError`
- [ ] Rate limiting: client reads `X-RateLimit-Remaining` and `X-RateLimit-Reset` from response headers
- [ ] Rate limiting: on 429 response, client retries with exponential backoff (max 3 retries)
- [ ] A `healthCheck()` method calls `GET /api/v1/health` and returns `{ status: "ok" }` -- this is useful for verifying connectivity and auth

---

## Tech Analysis

- **Node 20+ fetch is available.** The `package.json` specifies `"engines": { "node": ">=20.19 <22 || >=22.12" }`, so native `fetch` is available server-side. No need for `node-fetch` or `undici`.
- **The `.server.ts` convention** is already used in the app (`shopify.server.ts`, `db.server.ts`). React Router/Vite tree-shakes these from the client bundle. Follow this pattern.
- **TypeScript strict mode is enabled** in `tsconfig.json`. All types must be explicit -- no `any` types in the public API.
- **The platform API response format** follows Laravel conventions:
  - Success: `{ data: T }` for single resources, `{ data: T[], links: {...}, meta: {...} }` for paginated lists
  - Errors: `{ message: string, errors?: { [field: string]: string[] } }` for 422 validation
  - Simple errors: `{ message: string }` for 401, 403, 404, 500
- **Rate limit headers** from the platform (Laravel's `throttle` middleware):
  - `X-RateLimit-Limit` -- max requests per window
  - `X-RateLimit-Remaining` -- requests remaining
  - `Retry-After` -- seconds until rate limit resets (only on 429 responses)
- **The existing codebase uses semicolons** (see `shopify.server.ts`, `db.server.ts`). Follow this convention.
- **No `app/services/` directory exists yet.** It needs to be created.
- **Environment variables** are injected by Shopify CLI during development (`shopify app dev`). `FIBERMADE_API_URL` will be a new env var. For production, it will be set in the app's hosting environment.

## References

- `shopify/app/shopify.server.ts` -- example of a server-side service module, exports pattern
- `shopify/app/db.server.ts` -- example of `.server.ts` convention with singleton pattern
- `shopify/tsconfig.json` -- TypeScript configuration (strict mode, ES2022 target)
- `shopify/package.json` -- Node engine requirements, existing dependencies
- `shopify/vite.config.ts` -- Build configuration, server-side module handling
- `platform/app/Http/Controllers/Api/ApiController.php` -- API response format (successResponse, createdResponse, errorResponse, notFoundResponse)
- `platform/routes/api.php` -- API route structure and middleware (auth:sanctum, throttle:api)

## Files

- Create `shopify/app/services/fibermade-client.server.ts` -- FibermadeClient class with HTTP infrastructure, auth, error handling, rate limiting
- Create `shopify/app/services/fibermade-client.types.ts` -- TypeScript interfaces for API responses, errors, and rate limit info
