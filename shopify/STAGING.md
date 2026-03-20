# Shopify App — Staging Deployment

Staging uses the **fibermade-staging** app in the Shopify Partner Dashboard and deploys to
`https://staging.shopify.fibermade.app`.

---

## 1. Server Setup

Follow the same steps as [DEPLOY.md](DEPLOY.md) but with staging values:

### Forge Site

| Setting | Value |
|---|---|
| Domain | `staging.shopify.fibermade.app` |
| Project Type | Static / HTML |
| Web Directory | `/` |

### Environment

Forge > Site > Environment. Use `.env.staging` as the template:

```env
NODE_ENV=production
PORT=3000

DATABASE_URL=postgresql://forge:@127.0.0.1:5432/shopify

PRISMA_FIELD_ENCRYPTION_KEY=<generate with: npx cloak generate>

SHOPIFY_API_KEY=<Client ID from fibermade-staging app in Shopify Partners>
SHOPIFY_API_SECRET=<Client Secret from fibermade-staging app in Shopify Partners>
SCOPES=write_products
SHOPIFY_APP_URL=https://staging.shopify.fibermade.app

FIBERMADE_API_URL=https://staging.fibermade.app
FIBERMADE_URL=https://staging.fibermade.app
```

### Daemon

Forge > Server > Daemons > New Daemon:

| Setting | Value |
|---|---|
| Command | `node /home/forge/staging.shopify.fibermade.app/shopify/build/server/index.js` |
| Working Directory | `/home/forge/staging.shopify.fibermade.app/shopify` |
| User | `forge` |
| Processes | 1 |

### Deployment Script

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

cp $FORGE_SITE_PATH/.env $FORGE_SITE_PATH/shopify/.env

cd $FORGE_SITE_PATH/shopify

npm ci
npm run build
npx prisma migrate deploy

sudo -S supervisorctl restart daemon-DAEMON_ID:*
```

Replace `DAEMON_ID` with the actual daemon ID from Forge.

---

## 2. Shopify Partner Dashboard — Releasing a Version

After deploying code to the server, you must release a new version in the Shopify Partner Dashboard
for any changes to scopes, webhooks, or app URLs to take effect.

### Preferred: Shopify CLI (matches production workflow)

Run locally from the repo root:

```bash
cd shopify
npx shopify app config link   # first time only — links to fibermade-staging
npx shopify app deploy
```

This reads `shopify.app.toml` and pushes all config automatically.

### Manual: Partner Dashboard UI

Partners > Apps > **fibermade-staging** > Versions > Create version.

Fill in the form:

| Field | Value |
|---|---|
| **App URL** | `https://staging.shopify.fibermade.app/` |
| **Scopes** | `write_products` |
| **Redirect URLs** | `https://staging.shopify.fibermade.app/auth/callback` |

Leave Optional scopes blank. Click **Release**.

> **Important:** After releasing a version with new or changed scopes, you must uninstall and
> reinstall the app in your dev store. Shopify only issues an updated access token (with the new
> scopes) during the OAuth install flow. Existing sessions keep the old scopes until reinstalled.

---

## 3. After Any Scope Change — Reinstall the App

1. In your dev Shopify store: Settings > Apps > **fibermade-staging** > Delete
2. Reinstall via the app's install URL or Shopify Partners > Apps > fibermade-staging > Test on development store
3. Complete the OAuth flow — the new token will have the updated scopes

---

## 4. Staging Checklist

- [ ] Forge env vars set (especially `PRISMA_FIELD_ENCRYPTION_KEY`, `SHOPIFY_API_KEY`, `SHOPIFY_API_SECRET`)
- [ ] Daemon running: Forge > Server > Daemons
- [ ] Prisma migrations applied (check daemon log)
- [ ] `https://staging.shopify.fibermade.app` loads the app
- [ ] Shopify version released with correct scopes (`write_products,write_product_media`)
- [ ] App reinstalled in dev store after any scope change
- [ ] Import products succeeds (5+ products show after import completes)
