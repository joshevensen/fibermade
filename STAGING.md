# Staging Environment

Both apps run on a single DigitalOcean droplet managed via [Laravel Forge](https://forge.laravel.com).

| App | Domain |
|---|---|
| Platform (Laravel) | `staging.fibermade.app` |
| Shopify (Node.js) | `staging.shopify.fibermade.app` |

---

## Prerequisites

- Forge account connected to DigitalOcean (Forge > Integrations > DigitalOcean)
- GitHub connected to Forge (Forge > Source Control > GitHub)
- DNS for both domains pointed at the server's public IP before requesting SSL

---

## 1. Provision the Server

Forge > Create Server:

| Setting | Value |
|---|---|
| Provider | DigitalOcean |
| Type | **Application** |
| Region | Your preferred DO region |
| Size | 4GB RAM minimum |
| PHP Version | 8.4 |
| Database | PostgreSQL |
| Node.js | Yes |

After provisioning, confirm the Node.js version meets the Shopify app requirement (`>=20.19 <22` or `>=22.12`):

```bash
node --version
```

If the version is wrong, install Node 22 via the [NodeSource installer](https://github.com/nodesource/distributions) or `nvm`.

---

## 2. Create Databases

Forge > Server > Storage > Databases, create two databases:

- `platform`
- `shopify`

---

## 3. Platform Site (staging.fibermade.app)

### Create the Site

Forge > Server > Sites > Add Site:

| Setting | Value |
|---|---|
| Domain | `staging.fibermade.app` |
| Project Type | PHP/Laravel |
| Root Directory | `/platform` |
| Web Directory | `/public` |
| PHP Version | 8.4 |

### Connect Repository

- Provider: GitHub
- Repository: `your-org/fibermade`
- Branch: `main`

### Environment

Forge > Site > Environment. Use platform/.env.staging

### Deployment Script

Forge > Site > App > Deployment Script (zero-downtime):

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY/platform

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan migrate --force

npm ci || npm install
npm run build

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

### Queue Worker

Forge > Site > Processes > Background Processes > Add background process:

| Field | Value |
|---|---|
| Name | `queue` |
| php | PHP 8.4 |
| connection | `redis` |
| processes | 1 |
| --queue | `default` |
| --timeout | 60 |
| --tries | 3 |

### Scheduler

Forge > Server > Scheduler > Add Job:

| Setting | Value |
|---|---|
| Name | `scheduler` |
| Command | `php /home/forge/staging.fibermade.app/platform/artisan schedule:run` |
| User | `forge` |
| Frequency | Every Minute |

### SSL

Forge > Site > Domains > Add SSL Certificate > Let's Encrypt (HTTP-01).
DNS must already point to this server before requesting.

---

## 4. Shopify Site (staging.shopify.fibermade.app)

### Create the Site

Forge > Server > Sites > Add Site:

| Setting | Value |
|---|---|
| Domain | `staging.shopify.fibermade.app` |
| Project Type | Static / HTML |
| Root Directory | `/shopify` |
| Web Directory | `/` |

In Advanced settings:
- **Zero downtime deployments**: Off (the deploy script uses `git pull`, not Forge's release macros)

### Connect Repository

- Provider: GitHub
- Repository: `your-org/fibermade`
- Branch: `main`

### Environment

Forge > Site > Environment:

```env
NODE_ENV=production
PORT=3000

DATABASE_URL=postgresql://forge:<password>@127.0.0.1:5432/shopify

SHOPIFY_API_KEY=<staging app key from Shopify Partners>
SHOPIFY_API_SECRET=<staging app secret from Shopify Partners>
SCOPES=write_products,write_product_media
SHOPIFY_APP_URL=https://staging.shopify.fibermade.app

# Generate with: npx cloak generate — must match on all servers sharing this database.
PRISMA_FIELD_ENCRYPTION_KEY=
```

### Create the Daemon

Before writing the deployment script, create the background process so you have its ID.

Forge > Server > Processes > Background Processes > Add background process:

| Setting | Value |
|---|---|
| Name | `shopify` |
| Command | `/usr/bin/node /home/forge/staging.shopify.fibermade.app/shopify/node_modules/@react-router/serve/dist/cli.js ./build/server/index.js` |
| Working Directory | `/home/forge/staging.shopify.fibermade.app/shopify` |
| User | `forge` |
| Processes | 1 |

Note the numeric ID shown in the list after saving (e.g., `12345`). You'll need it for the deployment script.

### Deployment Script

Forge > Site > App > Deployment Script. Replace `DAEMON_ID` with the actual daemon ID:

```bash
REPO_ROOT="/home/forge/staging.shopify.fibermade.app"
SHOPIFY_DIR="$REPO_ROOT/shopify"

cd $REPO_ROOT
git pull origin $FORGE_SITE_BRANCH

cd $SHOPIFY_DIR

npm ci
npm run build
npx prisma migrate deploy

sudo -S supervisorctl restart daemon-DAEMON_ID:*
```

### Nginx Configuration

Forge > Site > Domains > Nginx configuration > select `staging.shopify.fibermade.app` from the File dropdown.

Replace the generated config entirely. The key line is `include forge-conf/SERVER_ID/server/*;` — Forge writes SSL certificates and challenge tokens there, so it must stay inside the server block:

```nginx
# FORGE CONFIG (DO NOT REMOVE!)
include forge-conf/SERVER_ID/before/*;

server {
    listen 80;
    listen [::]:80;
    server_name staging.shopify.fibermade.app;
    server_tokens off;
    root /home/forge/staging.shopify.fibermade.app/shopify;

    # FORGE SSL (DO NOT REMOVE!)
    # ssl_certificate;
    # ssl_certificate_key;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_dhparam /etc/nginx/dhparams.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    # FORGE CONFIG (DO NOT REMOVE!) - Forge writes SSL certs and challenge tokens here
    include forge-conf/SERVER_ID/server/*;

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /favicon.svg { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ /\.(?!well-known).* {
        deny all;
    }

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

# FORGE CONFIG (DO NOT REMOVE!)
include forge-conf/SERVER_ID/after/*;
```

Replace `SERVER_ID` with the numeric server ID in the generated config (e.g. `3078639`).

### SSL

Forge > Site > Domains > Add SSL Certificate > Let's Encrypt (HTTP-01).

### Shopify CLI

After the site is live, link the Shopify staging app config and deploy it:

```bash
cd shopify
npx shopify app config use staging
npx shopify app deploy
```

The staging app in Shopify Partners must have:
- App URL: `https://staging.shopify.fibermade.app`
- Redirect URL: `https://staging.shopify.fibermade.app/auth/callback`

---

## 5. GitHub Actions — Auto Deploy on Push

Disable push-to-deploy on both sites (Forge > Site > App > disable "Deploy on Push").
Instead, GitHub Actions deploys only the app whose files changed.

### Forge Deploy Hook URLs

Each site has a unique deploy hook URL (Forge > Site > App > Deployment Trigger URL).
Add them as GitHub repository secrets:

| Secret | Site |
|---|---|
| `FORGE_DEPLOY_PLATFORM_STAGING` | Platform staging |
| `FORGE_DEPLOY_SHOPIFY_STAGING` | Shopify staging |

### Workflow

Create `.github/workflows/deploy-staging.yml`:

```yaml
name: Deploy to Staging

on:
  push:
    branches: [main]

jobs:
  platform:
    name: Platform
    runs-on: ubuntu-latest
    if: |
      contains(toJson(github.event.commits.*.modified), 'platform/') ||
      contains(toJson(github.event.commits.*.added), 'platform/')
    steps:
      - run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_PLATFORM_STAGING }}"

  shopify:
    name: Shopify
    runs-on: ubuntu-latest
    if: |
      contains(toJson(github.event.commits.*.modified), 'shopify/') ||
      contains(toJson(github.event.commits.*.added), 'shopify/')
    steps:
      - run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_SHOPIFY_STAGING }}"
```
