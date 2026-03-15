# Platform — Production Servers

The production platform runs across four servers managed via [Laravel Forge](https://forge.laravel.com) on DigitalOcean.

```
fibermade.app  →  Load Balancer
                   ├── Web Server 1  (PHP-FPM + Nginx)
                   └── Web Server 2  (PHP-FPM + Nginx)

Prime Server (not public-facing)
   ├── Queue workers
   ├── Task scheduler
   └── Redis

All servers → DO Managed PostgreSQL
```

See [DEPLOY.md](DEPLOY.md) for installing the app and configuring deployments on these servers.

---

## Prerequisites

- All four servers must be in the **same DO region** and **same VPC** (private networking)
- Provision the DO Managed PostgreSQL cluster **before** the servers so you have the connection string ready
- DNS for `fibermade.app` pointed at the **load balancer's** public IP (not the web servers)

---

## 1. DO Managed PostgreSQL

In DigitalOcean, create a managed PostgreSQL cluster:

- **Plan**: Choose based on expected load (start with the basic 1GB/1vCPU plan and scale up)
- **Region**: Same region as your servers
- **Database name**: `fibermade_platform`

After provisioning:

1. Go to the cluster's **Trusted Sources** tab and add all four servers so they can connect
2. Note the **private network connection string** (hostname ending in `.private.do-user.com`) — use this in `.env` to keep traffic on DO's private network

---

## 2. Provision the Four Servers

Provision all servers in Forge (Forge > Create Server), all in the **same DO region** with **private networking enabled**.

### Load Balancer

| Setting | Value |
|---|---|
| Type | **Load Balancer** |
| Region | Your DO region |
| Size | 1GB (Nginx only — minimal resources needed) |
| Name | `platform-lb` |

### Web Servers (×2)

Provision two identical servers:

| Setting | Value |
|---|---|
| Type | **Web** |
| Region | Same DO region |
| Size | 2GB+ RAM |
| PHP Version | 8.4 |
| Database | None (using managed PostgreSQL) |
| Node.js | Yes (for `npm run build` in deploy script) |
| Names | `platform-web-1`, `platform-web-2` |

### Prime Server

| Setting | Value |
|---|---|
| Type | **Application** |
| Region | Same DO region |
| Size | 2GB+ RAM |
| PHP Version | 8.4 |
| Database | None (using managed PostgreSQL) |
| Node.js | Yes |
| Name | `platform-prime` |

The Application type installs Redis automatically. The prime server is not publicly accessible for HTTP — it only runs queues, the scheduler, and Redis.

---

## 3. Private Networking & Firewall

After all servers are provisioned:

### Redis (Prime → Web Servers)

Redis on the prime server must be reachable by the web servers on its private IP.

**Restrict Redis to the private network** — SSH into the prime server and edit `/etc/redis/redis.conf`:

```
bind 127.0.0.1 <prime-private-ip>
```

Then restart Redis:
```bash
sudo systemctl restart redis
```

**Open Redis port on the prime server's firewall** — Forge > `platform-prime` > Network > Add Rule:

| Setting | Value |
|---|---|
| Type | Custom |
| Protocol | TCP |
| Port | 6379 |
| From | `<web-1-private-ip>`, `<web-2-private-ip>` |

### PostgreSQL (All Servers → Managed DB)

No extra firewall rules needed — managed PostgreSQL access is controlled by DO's Trusted Sources (configured in step 1).

### Web Servers (Not Publicly Accessible for HTTP)

The web servers should only receive HTTP traffic from the load balancer, not directly from the internet. Forge > each web server > Network:

- Remove or restrict the rule allowing port 80/443 from all IPs
- Add a rule allowing port 80 only from the load balancer's private IP

> This is optional but recommended. At minimum, ensure your app does not expose sensitive endpoints without load balancer protection.

---

## 4. Laravel Trusted Proxies

Since all HTTP traffic arrives via the load balancer, Laravel must trust it to correctly resolve the client IP, scheme (HTTPS), and host.

In `app/Http/Middleware/TrustProxies.php`:

```php
protected $proxies = '*';

protected $headers =
    Request::HEADER_X_FORWARDED_FOR |
    Request::HEADER_X_FORWARDED_HOST |
    Request::HEADER_X_FORWARDED_PORT |
    Request::HEADER_X_FORWARDED_PROTO;
```
