status: pending

# Story 1.2: Prompt 2 -- Account Linking UI

## Context

Prompt 1 created the `FibermadeConnection` Prisma model and the server action at `/app/connect` that verifies a Fibermade API token, creates an Integration record in the platform, and stores the connection locally. The action accepts a POST with the API token and returns `{ success: true, integrationId }` or `{ success: false, error, field? }`. No UI exists for the linking flow yet -- the app dashboard (`/app`) still shows the template's "Generate a product" demo page.

## Goal

Build the account linking UI page that merchants see after installing the app. The page collects their Fibermade API token, submits it to the linking action, shows loading/error/success states, and redirects to the connected dashboard on success. This replaces the template demo page as the primary app experience for unlinked shops.

## Non-Goals

- Do not modify the server action from Prompt 1 (it already handles all backend logic)
- Do not build a connected/dashboard state (that's Story 1.3 -- the status check and connected view)
- Do not add a disconnect/unlink option (that's Story 1.3)
- Do not add Shopify API scope requests or additional OAuth flows
- Do not modify the app layout or navigation structure

## Constraints

- Use Shopify Polaris web components (`<s-page>`, `<s-section>`, `<s-text-field>`, `<s-button>`, etc.) for all UI -- this is an embedded Shopify app and must look native
- The linking page lives at `/app/connect` (the same route as the action from Prompt 1)
- Use React Router's `useFetcher` for form submission (same pattern as the existing `app._index.tsx`)
- Show clear loading state while the action is processing (the API calls can take a few seconds)
- Show validation errors inline (e.g., "Invalid API token" next to the input field)
- Show a success toast via `shopify.toast.show()` on successful connection (using `useAppBridge()`)
- After successful linking, redirect to `/app` (the main dashboard)
- The page should explain what the merchant needs to do: get their API token from the Fibermade platform and paste it here
- The app dashboard (`/app`) loader should check if the shop is already connected and redirect to the appropriate view -- if not connected, redirect to `/app/connect`; if connected, show a simple "Connected" state (Story 1.3 will expand this)

## Acceptance Criteria

- [ ] `/app/connect` renders a linking page with:
  - Page heading (e.g., "Connect to Fibermade")
  - Brief instructions explaining the merchant needs their Fibermade API token
  - A text input field for the API token
  - A "Connect" submit button
- [ ] Submitting the form calls the action via `useFetcher`
- [ ] While submitting: button shows loading state, input is disabled
- [ ] On success: shows toast notification, redirects to `/app`
- [ ] On error (invalid token): shows inline error message on the token field
- [ ] On error (shop already linked): shows appropriate message
- [ ] On error (network/server): shows general error banner
- [ ] The `/app` loader checks for an existing `FibermadeConnection` record for the current shop
- [ ] If no connection exists: the `/app` index page shows a prompt/redirect to `/app/connect`
- [ ] If a connection exists: the `/app` index page shows a basic "Connected to Fibermade" confirmation (simple -- Story 1.3 expands this)

---

## Tech Analysis

- **Shopify Polaris web components** are the UI framework. The existing pages use `<s-page>`, `<s-section>`, `<s-paragraph>`, `<s-button>`, `<s-link>`, `<s-stack>`, `<s-box>`. For the form, use `<s-text-field>` for the token input and `<s-button>` for submit. Refer to Shopify's Polaris web component docs for available attributes.
- **`useFetcher` pattern** from `app._index.tsx`: call `fetcher.submit({ token }, { method: "POST" })`, check `fetcher.state` for loading, and read `fetcher.data` for the action response.
- **App Bridge toast**: `const shopify = useAppBridge(); shopify.toast.show("Connected to Fibermade");` -- same pattern as the existing product creation toast.
- **Redirect after success**: Use `useEffect` to watch `fetcher.data` and call `navigate("/app")` (from `useNavigate()`) when `success: true` is returned.
- **Checking connection status in the `/app` loader**: In `app._index.tsx`'s loader, query `db.fibermadeConnection.findUnique({ where: { shop: session.shop } })`. Return `{ connected: true/false }` to the component. In the component, if not connected, either show a banner with a link to `/app/connect` or use `<Navigate to="/app/connect" />`.
- **The connect page loader**: The `app.connect.tsx` route can have a `loader` that checks if already connected and redirects to `/app` if so (prevent re-linking from the UI).
- **Error display**: For validation errors, `<s-text-field>` supports an `error` attribute/prop for inline error messages. For general errors, use `<s-banner tone="critical">` at the top of the page.
- **Token input type**: Use `type="password"` or equivalent on the text field so the token isn't visible in plain text. Shopify's `<s-text-field>` may support a `type` attribute.
- **The template demo page** (`app._index.tsx`) should be replaced -- the "Generate a product" demo is scaffolding that's no longer needed.

## References

- `shopify/app/routes/app._index.tsx` -- existing page pattern: loader, action, useFetcher, useAppBridge, Polaris components
- `shopify/app/routes/app.connect.tsx` -- action from Prompt 1 (add loader and default export component)
- `shopify/app/routes/app.tsx` -- app layout with admin auth and navigation
- `shopify/app/db.server.ts` -- Prisma client for querying FibermadeConnection
- `shopify/app/shopify.server.ts` -- authenticate.admin for session access

## Files

- Modify `shopify/app/routes/app.connect.tsx` -- add loader (redirect if already connected) and default export component (linking form UI)
- Modify `shopify/app/routes/app._index.tsx` -- replace template demo with connection status check; redirect to /app/connect if not linked, show connected state if linked
