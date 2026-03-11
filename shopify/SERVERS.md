# Shopify App — Production Servers

The production Shopify app runs across three servers managed via [Laravel Forge](https://forge.laravel.com) on DigitalOcean.

```
shopify.fibermade.app  →  Load Balancer
                            ├── Web Server 1  (Node.js, port 3000)
                            └── Web Server 2  (Node.js, port 3000)

All servers → DO Managed PostgreSQL
```

The app uses PostgreSQL for Shopify session storage and Fibermade connection data. Because sessions are stored in the database — not in memory or on the filesystem — multiple Node.js instances can handle requests without sticky sessions.

See [DEPLOY.md](DEPLOY.md) for installing the app and configuring deployments on these servers.

---

## Prerequisites

- All three servers in the **same DO region** and **same VPC** (private networking)
- DO Managed PostgreSQL cluster provisioned **before** the servers
- DNS for `shopify.fibermade.app` pointed at the **load balancer's** public IP

---

## 1. DO Managed PostgreSQL

In DigitalOcean, create a managed PostgreSQL cluster (can be the same cluster as the platform app with a separate database, or a dedicated cluster):

- **Database name**: `fibermade_shopify`
- **Region**: Same as your servers
- **Trusted Sources**: Add all three servers after provisioning them

Note the **private network connection string** for use in `.env`.

---

## 2. Provision the Three Servers

Provision all servers in Forge (Forge > Create Server), all in the **same DO region** with **private networking enabled**.

### Load Balancer

| Setting | Value |
|---|---|
| Type | **Load Balancer** |
| Region | Your DO region |
| Size | 1GB |
| Name | `shopify-lb` |

### Web Servers (×2)

| Setting | Value |
|---|---|
| Type | **Web** |
| Region | Same DO region |
| Size | 2GB+ RAM |
| PHP | None (Node.js app — skip PHP) |
| Node.js | Yes |
| Names | `shopify-web-1`, `shopify-web-2` |

> The Shopify app is a React Router 7 SSR app started with `react-router-serve`. It requires Node `>=20.19 <22` or `>=22.12`. After provisioning, SSH in and confirm with `node --version`. Install Node 22 via [NodeSource](https://github.com/nodesource/distributions) if needed.

---

## 3. Shopify App Store Requirements

Before launch, the Shopify App Store requires:

- [ ] Valid TLS on all endpoints (Let's Encrypt via Forge)
- [ ] HTTPS enforced — no mixed content
- [ ] OAuth callback at `https://shopify.fibermade.app/auth/callback`
- [ ] Session token auth via App Bridge (no third-party cookies)
- [ ] GDPR compliance webhooks responding with 2xx:
  - `customers/data_request`
  - `customers/redact`
  - `shop/redact`
- [ ] Webhook HMAC verification before processing
- [ ] GraphQL Admin API used (REST is legacy as of Oct 2024)
- [ ] Only required scopes requested
- [ ] Publicly linked privacy policy on the App Store listing
