# Platform — Production Deployment

This covers installing the platform app on each production server and keeping it deployed.

See [SERVERS.md](SERVERS.md) for provisioning the servers first.

---

## 1. Install the Site on Web Servers

Repeat the following steps on both `platform-web-1` and `platform-web-2`.

### Create the Site

Forge > Server > Sites > Add Site:

| Setting | Value |
|---|---|
| Domain | `fibermade.app` |
| Project Type | PHP/Laravel |
| Root Directory | `/platform` |
| Web Directory | `/public` |
| PHP Version | 8.4 |

### Connect Repository

- Provider: GitHub
- Repository: `your-org/fibermade`
- Branch: `main`
- Push-to-Deploy: **Disabled** (GitHub Actions handles deploys)

### Environment

Forge > Site > Environment:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://fibermade.app

DB_CONNECTION=pgsql
DB_HOST=<DO managed PostgreSQL private hostname>
DB_PORT=25060
DB_DATABASE=fibermade_platform
DB_USERNAME=<managed DB user>
DB_PASSWORD=<managed DB password>
DB_SSLMODE=require

REDIS_HOST=<platform-prime private IP>
REDIS_PORT=6379
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_DRIVER=redis

# Add remaining app-specific keys (mail, Stripe, etc.)
```

### Stripe webhook

The app creates accounts and users only when Stripe sends **`checkout.session.completed`** to your webhook. It also uses other events for subscription status. Configure the webhook in the [Stripe Dashboard](https://dashboard.stripe.com/webhooks) (use the same Stripe account as your live keys).

1. **Environment variables** (Forge > Site > Environment) — set for each site that serves HTTP (web and optionally prime if it receives webhooks):
   - `STRIPE_KEY` — publishable key (live mode for production).
   - `STRIPE_SECRET` — secret key (live mode).
   - `STRIPE_WEBHOOK_SECRET` — signing secret from the webhook endpoint (step 2).
   - `STRIPE_PRICE_ID` — Stripe Price ID for the subscription used at registration (e.g. `price_xxx`).

2. **Webhook endpoint** — In Stripe: Developers → Webhooks → Add endpoint.
   - **Endpoint URL:** `https://fibermade.app/webhooks/stripe` (production). For staging use your staging URL, e.g. `https://staging.fibermade.app/webhooks/stripe`.
   - **Events to send:** Subscribe to at least:
     - `checkout.session.completed` — required for registration (creates Account, Creator, User).
     - `invoice.payment_succeeded` — sets subscription status to Active.
     - `invoice.payment_failed` — sets subscription status to Past due.
     - `customer.subscription.deleted` — sets subscription status to Inactive.
     - `charge.refunded` — sets subscription status to Refunded (charges within 30 days).
   - After creating the endpoint, open it and reveal **Signing secret**; put that value in `STRIPE_WEBHOOK_SECRET` in your `.env`.

If the webhook is missing or the signing secret is wrong, checkout will succeed in Stripe but no user or account will be created in your database.

### Deployment Script

Forge > Site > App > Deployment Script (zero-downtime). Migrations are excluded — they run only on the prime server (see section 2).

```bash
$CREATE_RELEASE()

cd $FORGE_RELEASE_DIRECTORY/platform

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs

$FORGE_PHP artisan optimize
$FORGE_PHP artisan storage:link

npm ci || npm install
npm run build

$ACTIVATE_RELEASE()

$RESTART_QUEUES()
```

---

## 2. Install the Site on the Prime Server

The prime server runs the same codebase for queue workers and the scheduler but does not serve HTTP traffic.

### Create the Site

Forge > `platform-prime` > Sites > Add Site:

| Setting | Value |
|---|---|
| Domain | `prime.fibermade.app` (internal label — no public DNS needed) |
| Project Type | PHP/Laravel |
| Root Directory | `/platform` |
| Web Directory | `/public` |
| PHP Version | 8.4 |

### Connect Repository

Same repo and branch as the web servers. Push-to-Deploy **disabled**.

### Environment

Same `.env` as the web servers. The prime server connects to the same managed PostgreSQL and its own local Redis.

```env
REDIS_HOST=127.0.0.1  # Redis is local on the prime server
```

### Deployment Script

Forge > Site > App > Deployment Script (zero-downtime). This is the only server that runs migrations.

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

### Queue Workers

Forge > Site > Processes > Background Processes > Add background process:

| Field | Value |
|---|---|
| Name | `queue` |
| php | PHP 8.4 |
| connection | `redis` |
| processes | 2 (adjust based on load) |
| --queue | `default` |
| --timeout | 60 |
| --tries | 3 |

Add additional workers for other queues (e.g., `high`, `low`) as needed.

### Scheduler

Forge > Server > Scheduler > Add Job:

| Setting | Value |
|---|---|
| Name | `scheduler` |
| Command | `php /home/forge/prime.fibermade.app/platform/artisan schedule:run` |
| User | `forge` |
| Frequency | Every Minute |

---

## 3. Configure the Load Balancer

### Add the Balanced Site

Forge > `platform-lb` > Sites > Add Balanced Site:

| Setting | Value |
|---|---|
| Domain | `fibermade.app` |
| Servers | `platform-web-1`, `platform-web-2` |

Forge configures Nginx on the load balancer to round-robin across both web servers using their private IPs.

### SSL

Forge > `platform-lb` > Site > Domains > Add SSL Certificate > Let's Encrypt (HTTP-01).

DNS for `fibermade.app` must point to the **load balancer's public IP** before requesting the certificate.

---

## 5. GitHub Actions — Deploy to Production

Production deploys are triggered manually from the GitHub Actions UI (not on every push).

### Forge Deploy Hook URLs

Each site has a unique deploy hook URL (Forge > Site > App > Deployment Trigger URL).
Add them as GitHub repository secrets:

| Secret | Server |
|---|---|
| `FORGE_DEPLOY_PLATFORM_PROD_WEB1` | `platform-web-1` |
| `FORGE_DEPLOY_PLATFORM_PROD_WEB2` | `platform-web-2` |
| `FORGE_DEPLOY_PLATFORM_PROD_PRIME` | `platform-prime` |

### Workflow

Add the following job to `.github/workflows/deploy-production.yml` (create the file if it doesn't exist):

```yaml
name: Deploy to Production

on:
  workflow_dispatch:  # Manual trigger only

jobs:
  platform:
    name: Platform
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to web-1
        run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_PLATFORM_PROD_WEB1 }}"
      - name: Deploy to web-2
        run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_PLATFORM_PROD_WEB2 }}"
      - name: Deploy to prime
        run: curl -s -X POST "${{ secrets.FORGE_DEPLOY_PLATFORM_PROD_PRIME }}"
```

Trigger it from GitHub > Actions > "Deploy to Production" > Run workflow.

---

## 6. First Deploy Checklist

Before going live, verify on each server:

- [ ] `.env` is set (Forge > Site > Environment)
- [ ] Database connection works: `php artisan db:show` (via Forge SSH)
- [ ] Redis connection works: `php artisan tinker` → `Cache::put('test', 1)` / `Cache::get('test')`
- [ ] Migrations ran: `php artisan migrate:status`
- [ ] Queue workers are running: Forge > Site > Queues
- [ ] Scheduler is active: Forge > Server > Scheduler
- [ ] SSL is active on the load balancer
- [ ] `fibermade.app` resolves to the load balancer IP (not a web server IP)
- [ ] `https://fibermade.app` loads correctly and shows HTTPS
