# Shopify App — Production Deployment

This covers installing the Shopify app on each production server and keeping it deployed.

See [SERVERS.md](SERVERS.md) for provisioning the servers first.

---

## 1. Install the Site on Web Servers

The steps below apply to both servers except the deployment script, which differs:
`shopify-web-1` runs Prisma migrations; `shopify-web-2` does not. This avoids racing
to migrate the same database on every deploy without needing a dedicated server for it.

### Create the Site

Forge > Server > Sites > Add Site:

| Setting | Value |
|---|---|
| Domain | `shopify.fibermade.app` |
| Project Type | Static / HTML |
| Web Directory | `/` |

### Connect Repository

- Provider: GitHub
- Repository: `your-org/fibermade`
- Branch: `main`
- Push-to-Deploy: **Disabled** (GitHub Actions handles deploys)

### Environment

Forge > Site > Environment:

```env
NODE_ENV=production
PORT=3000

DATABASE_URL=postgresql://<user>:<pass>@<DO private hostname>:25060/fibermade_shopify?sslmode=require

SHOPIFY_API_KEY=<production app key from Shopify Partners>
SHOPIFY_API_SECRET=<production app secret from Shopify Partners>
SCOPES=write_products,write_product_media
SHOPIFY_APP_URL=https://shopify.fibermade.app

# Generate with: npx cloak generate — must be identical on both web servers.
# Changing this will break decryption of existing encrypted DB fields.
PRISMA_FIELD_ENCRYPTION_KEY=
```

### Create the Daemon

Before writing the deployment script, create the Supervisor daemon on this server to get its ID.

Forge > Server > Daemons > New Daemon:

| Setting | Value |
|---|---|
| Command | `node /home/forge/shopify.fibermade.app/shopify/build/server/index.js` |
| Working Directory | `/home/forge/shopify.fibermade.app/shopify` |
| User | `forge` |
| Processes | 1 |

Note the daemon ID shown in the daemon list (e.g., `12345`). You need it in the deployment script.

### Deployment Script

Forge > Site > App > Deployment Script. Replace `DAEMON_ID` with the actual daemon ID for this server.

**shopify-web-1** (runs migrations):

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

# Forge writes .env to the site root; copy it into the shopify subdirectory
cp $FORGE_SITE_PATH/.env $FORGE_SITE_PATH/shopify/.env

cd $FORGE_SITE_PATH/shopify

npm ci
npm run build
npx prisma migrate deploy

sudo -S supervisorctl restart daemon-DAEMON_ID:*
```

**shopify-web-2** (skips migrations):

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

cp $FORGE_SITE_PATH/.env $FORGE_SITE_PATH/shopify/.env

cd $FORGE_SITE_PATH/shopify

npm ci
npm run build

sudo -S supervisorctl restart daemon-DAEMON_ID:*
```

### Nginx Configuration

Forge > Site > Nginx. Replace the generated config with:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name shopify.fibermade.app;
    root /home/forge/shopify.fibermade.app;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}
```

---

## 2. Configure the Load Balancer

### Add the Balanced Site

Forge > `shopify-lb` > Sites > Add Balanced Site:

| Setting | Value |
|---|---|
| Domain | `shopify.fibermade.app` |
| Servers | `shopify-web-1`, `shopify-web-2` |

Forge configures Nginx on the load balancer to round-robin across both web servers.

### SSL

Forge > `shopify-lb` > Site > Domains > Add SSL Certificate > Let's Encrypt (HTTP-01).

DNS for `shopify.fibermade.app` must point to the **load balancer's public IP** before requesting the certificate.

---

## 3. Shopify CLI Deploy

Deploying app config (webhooks, scopes, app URL) to Shopify is **separate from the Forge deploy** and uses the Shopify CLI. Run this locally after infrastructure is up and any time `shopify.app.toml` changes.

```bash
cd shopify
npx shopify app config use production
npx shopify app deploy
```

This pushes the `shopify.app.toml` configuration to Shopify Partners and registers all webhook subscriptions.

The production app in Shopify Partners must have:
- App URL: `https://shopify.fibermade.app`
- Redirect URL: `https://shopify.fibermade.app/auth/callback`

---

## 4. GitHub Actions — Deploy to Production

Production deploys are triggered manually from the GitHub Actions UI.

### Forge Deploy Hook URLs

Each site has a unique deploy hook URL (Forge > Site > App > Deployment Trigger URL).
Add them as GitHub repository secrets:

| Secret | Server |
|---|---|
| `FORGE_DEPLOY_SHOPIFY_PROD_WEB1` | `shopify-web-1` |
| `FORGE_DEPLOY_SHOPIFY_PROD_WEB2` | `shopify-web-2` |

### Workflow

Add the following job to `.github/workflows/deploy-production.yml` (create the file if it doesn't exist):

```yaml
name: Deploy to Production

on:
  workflow_dispatch:  # Manual trigger only

jobs:
  shopify:
    name: Shopify App
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to web-1
        run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_SHOPIFY_PROD_WEB1 }}"
      - name: Deploy to web-2
        run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_SHOPIFY_PROD_WEB2 }}"
```

Trigger it from GitHub > Actions > "Deploy to Production" > Run workflow.

---

## 5. First Deploy Checklist

Before going live, verify on each web server:

- [ ] `.env` is set and `ENCRYPTION_SECRET` matches on both servers
- [ ] Database connection works: `DATABASE_URL` is reachable via private network
- [ ] Daemon is running: Forge > Server > Daemons (status should be "Running")
- [ ] Nginx proxies to port 3000: `curl http://localhost:3000` from SSH
- [ ] Prisma migrations applied: check daemon log at `/home/forge/.forge/daemon-{id}.log`
- [ ] SSL active on the load balancer
- [ ] `shopify.fibermade.app` resolves to the load balancer IP
- [ ] `https://shopify.fibermade.app` loads the app correctly
- [ ] OAuth flow works end-to-end (install the app on a test store)
- [ ] Webhook delivery confirmed in Shopify Partners > your app > Webhooks
- [ ] `shopify app deploy` has been run for production config
