# Task 03 — Settings Page UI

## Starting Prompt

> I'm working through the Shopify connect token migration at `specs/shopify-connect-token/`. Please read `specs/shopify-connect-token/overview.md` and `specs/shopify-connect-token/tasks/03-settings-ui.md`, then implement Task 03 in full. Work through the checklist at the bottom of the task file. Tasks 01–02 are already complete. This task touches the Laravel platform only (`platform/` directory).

## Goal

Replace the "Generate token" flow in `ShopifyConnectionCard.vue` with a static connect token display. The token is always visible — no button required to generate it. Also expose the token via the settings page props and add a "Reset token" action.

## Backend Changes

### Settings page props

The `shopify` prop passed to `SettingsPage` needs to include `connect_token`. Find where the `shopify` prop is built (likely in `UserController` or a dedicated settings controller) and add it:

```php
'connect_token' => $account->shopify_connect_token,
```

### Reset token endpoint

Add a route and controller method to regenerate the connect token:

```
POST /creator/settings/shopify-connect-token/reset
```

Handler calls `$account->generateConnectToken()` and returns a JSON response with the new token:
```json
{ "connect_token": "new-uuid" }
```

This can live in `UserController` or a small dedicated controller — follow existing conventions.

## Frontend Changes

### `ShopifyConnectionCard.vue`

**Remove entirely:**
- `token` ref
- `loading` ref
- `copied` ref
- `errorMessage` ref
- `hasToken` computed
- `generateToken()` function
- `copyToken()` function (replace with inline clipboard write)
- The `getCsrfToken()` helper (if it moves to a shared composable — check if anything else uses it)
- "Generate token" button
- Token display block (`v-if="hasToken"`)

**Add:**
- `connectToken` prop (string) passed from parent
- A static token display — always visible:

```
Your Fibermade Connect Token
[ xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx ] [Copy]

Paste this into the Shopify app to connect your store.

[Reset token]
```

- Copy button writes `connectToken` to clipboard directly (same clipboard logic, just inline)
- "Reset token" is a small text button (destructive style) with a confirmation step before calling `POST /creator/settings/shopify-connect-token/reset`. On success, update the displayed token reactively.
- Reset confirmation can be an inline warning text revealed before the reset POST fires: "This will disconnect any stores currently linked with this token." with a "Yes, reset" button.

### `SettingsPage.vue`

- Pass `shopify.connect_token` down to `ShopifyConnectionCard` as a prop
- Update the `shopify` type definition to include `connect_token: string`

## What the Token Display Looks Like

When not connected:
```
Shopify Connection
Connect your Shopify store to Fibermade

● Not connected

Your Fibermade Connect Token
[ 3f8a1b2c-... ] [Copy]
Paste this into the Shopify app to connect your store.
[Reset token]
```

When connected:
```
Shopify Connection
Connect your Shopify store to Fibermade

● example.myshopify.com — Connected since March 1, 2026

Your Fibermade Connect Token
[ 3f8a1b2c-... ] [Copy]
Paste this into the Shopify app to connect your store.
[Reset token]
```

The token is shown in both states — creators may want to copy it again after connecting (e.g., to link a second store in future, or share with a dev).

## Checklist

- [ ] Add `connect_token` to the `shopify` prop in the settings page controller/action
- [ ] Add reset token route (`POST /creator/settings/shopify-connect-token/reset`) and handler
- [ ] Update `ShopifyConnectionCard.vue` — remove generate-token flow entirely
- [ ] Add `connectToken` prop to `ShopifyConnectionCard`
- [ ] Add static connect token display with copy button
- [ ] Add reset token button with inline confirmation
- [ ] Wire reset action to call the new endpoint and update the displayed token reactively
- [ ] Update `SettingsPage.vue` type definition and prop passing
- [ ] Run `npm run format`
- [ ] Manually verify: token displays on page load, copy works, reset generates a new token
