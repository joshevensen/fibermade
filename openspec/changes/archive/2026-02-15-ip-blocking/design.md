## Context

Fibermade currently has no request-level blocking mechanism. The platform uses Laravel 12's middleware stack with standard web and API guards, but lacks protection against sustained attacks from bad actors. During an attack scenario, the owner needs to quickly block IPs without requiring code deployments or server restarts.

The platform already uses Redis for session storage and caching, which we'll leverage for performant blocklist lookups.

## Goals / Non-Goals

**Goals:**
- Block malicious requests as early as possible in the request lifecycle (minimize resource consumption)
- Support multiple blocking strategies (individual IP, CIDR ranges, user agent patterns)
- Enable quick updates during active attacks (< 5 minutes from database change to enforcement)
- Automatic expiration of temporary blocks
- Zero-downtime deployment

**Non-Goals:**
- Admin UI for managing blocks (manual database management is acceptable)
- Advanced rate limiting or anomaly detection (separate concern, Laravel has rate limiting built-in)
- Block logging/analytics dashboard (can query database directly)
- IP geolocation or reputation scoring
- Blocking at infrastructure level (CloudFlare, nginx) - this is application-level

## Decisions

### 1. Middleware Placement

**Decision:** Register `BlockAccessMiddleware` as the first global middleware in `Kernel.php` after `TrustProxies` and `ValidatePostSize`.

**Rationale:** 
- Needs to execute early to minimize resource consumption from blocked requests
- Must run after `TrustProxies` to get accurate IP addresses behind load balancers
- Running before authentication/session middleware means blocked requests never touch those systems

**Alternative Considered:** Apply to specific middleware groups (`web`, `api`) - rejected because bad actors could still hit unprotected routes.

### 2. Database Schema

**Decision:** Single `blocked_accesses` table with polymorphic type field:

```sql
CREATE TABLE blocked_accesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('ip', 'ip_range', 'user_agent') NOT NULL,
    value VARCHAR(255) NOT NULL,
    reason TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type_value (type, value),
    INDEX idx_expires_at (expires_at)
);
```

**Rationale:**
- Single table keeps queries simple and fast (single cache key for entire blocklist)
- `type` field allows different matching strategies without schema changes
- `value` stores the actual pattern (IP, CIDR, user agent string)
- Indexes on `type` and `value` for fast lookups
- Index on `expires_at` for efficient expiration filtering

**Alternative Considered:** Separate tables for each type - rejected as unnecessary complexity for this use case.

### 3. Caching Strategy

**Decision:** Cache entire blocklist in Redis with 1-minute TTL, keyed as `blocklist:all`.

**Rationale:**
- 1-minute TTL provides balance between performance and rapid response during attacks
- Single cache key reduces Redis operations (one get vs N queries per request)
- Small blocklist size (likely < 100 entries) means entire list fits easily in memory
- Cache miss falls back to database query (graceful degradation)

**Cache Structure:**
```php
[
    'ip' => ['1.2.3.4', '5.6.7.8'],
    'ip_range' => ['10.0.0.0/8', '192.168.1.0/24'],
    'user_agent' => ['BadBot/1.0', 'Scraper']
]
```

**Alternative Considered:** 
- Individual cache keys per entry - more cache operations per request
- Longer TTL (5-15 minutes) - slower response during active attacks
- No caching - excessive database load

### 4. CIDR Range Matching

**Decision:** Use Laravel's built-in `IpUtils` class (Symfony component) for CIDR matching.

**Rationale:**
- Battle-tested implementation
- Handles IPv4 and IPv6
- Already included in Laravel dependencies

```php
use Symfony\Component\HttpFoundation\IpUtils;

IpUtils::checkIp($clientIp, $cidrRange); // returns bool
```

**Alternative Considered:** Custom CIDR implementation - unnecessary reinvention, higher risk of bugs.

### 5. User Agent Matching

**Decision:** Simple substring matching (case-insensitive) for user agent patterns.

**Rationale:**
- User agents are easily spoofed, so complex regex isn't worth the performance cost
- Substring matching catches common bad bots: `str_contains(strtolower($userAgent), strtolower($pattern))`
- Regex would allow more sophisticated patterns but adds attack surface (ReDoS)

**Alternative Considered:** Regex patterns - rejected due to performance and security concerns.

### 6. Expiration Handling

**Decision:** Filter out expired blocks when loading from database (before caching).

**Rationale:**
- Expired blocks never enter cache
- Keeps cache lean
- No background jobs needed for cleanup
- Optional periodic cleanup command for database maintenance

```php
BlockedAccess::where(function ($query) {
    $query->whereNull('expires_at')
          ->orWhere('expires_at', '>', now());
})->get();
```

**Alternative Considered:** Background job to delete expired blocks - adds complexity, not necessary with query-level filtering.

### 7. Response Handling

**Decision:** Return `403 Forbidden` with minimal JSON response:

```php
return response()->json(['message' => 'Forbidden'], 403);
```

**Rationale:**
- 403 is semantically correct (server understood but refuses to authorize)
- No details prevents information disclosure about blocking rules
- JSON response works for both web and API routes

**Alternative Considered:** 
- 404 to hide that blocking is active - misleading, makes debugging harder
- Custom error page - unnecessary for attackers, normal users should never see this

### 8. Middleware Logic Flow

```
Request → TrustProxies → BlockAccessMiddleware
                              ↓
                         Load blocklist (cache/db)
                              ↓
                         Check IP (exact + CIDR)
                              ↓
                         Check User Agent (substring)
                              ↓
                    Blocked? → 403 : Continue
```

## Risks / Trade-offs

### Risk: Cache Staleness During Active Attack
**Mitigation:** 1-minute TTL means blocks take effect within 60 seconds. If faster response needed, can flush cache manually: `Cache::forget('blocklist:all')`.

### Risk: False Positives Blocking Legitimate Users
**Mitigation:** 
- Always include `reason` when creating blocks for audit trail
- Test blocks in staging before production
- Owner has direct database access for quick unblocking

### Risk: Database Insert Performance During High-Load Attack
**Mitigation:** Database inserts are out-of-band (manual), not in request path. Middleware only reads from cache/db.

### Risk: IP Spoofing Behind Proxy
**Mitigation:** `TrustProxies` middleware must be configured correctly with trusted proxies. Middleware runs after `TrustProxies` to get accurate client IP.

### Risk: Blocking Shared IPs (Corporate Networks, VPNs)
**Mitigation:** 
- Use user agent blocking instead for shared IPs
- Temporary blocks with `expires_at` for quick rollback
- Monitor and adjust as needed

### Risk: Redis Cache Failure
**Mitigation:** Graceful fallback to database queries. Performance degrades but blocking continues to function.

### Risk: CIDR Misconfiguration Blocking Too Broadly
**Mitigation:** 
- Test CIDR ranges before adding to production
- Start with individual IPs, expand to ranges only when patterns emerge
- Include descriptive `reason` field for documentation

## Migration Plan

**Deployment Steps:**

1. **Deploy migration:**
   ```bash
   php artisan migrate
   ```
   Creates `blocked_accesses` table with indexes.

2. **Deploy code:**
   - New `BlockedAccess` model
   - New `BlockAccessMiddleware`
   - Updated `Kernel.php` with middleware registration

3. **Verify:**
   - Test with known blocked IP in staging
   - Check cache warming on first request
   - Verify expiration filtering works

**Rollback Strategy:**
- Remove middleware registration from `Kernel.php` (application change, no data loss)
- Rolling back migration loses any blocks created (acceptable - they can be re-created)

**Zero-Downtime:** Yes - middleware safely handles missing table during deployment window.
