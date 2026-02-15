# IP Blocking

The IP blocking system protects Fibermade from malicious actors by blocking requests based on IP addresses, CIDR ranges, or user agent patterns.

## Features

- Block individual IP addresses
- Block CIDR ranges (IPv4 and IPv6)
- Block by user agent patterns (case-insensitive substring matching)
- Automatic expiration of temporary blocks
- Redis caching with 1-minute TTL for performance
- Manual management via database

## Managing Blocks

Blocks are managed directly via database inserts, updates, and deletes. No UI is provided.

### Block an Individual IP Address

```sql
INSERT INTO blocked_accesses (type, value, reason, created_at, updated_at)
VALUES ('ip', '1.2.3.4', 'DDoS attack from this IP', NOW(), NOW());
```

### Block a CIDR Range

```sql
INSERT INTO blocked_accesses (type, value, reason, created_at, updated_at)
VALUES ('ip_range', '192.168.1.0/24', 'Entire subnet attacking', NOW(), NOW());
```

### Block by User Agent

```sql
INSERT INTO blocked_accesses (type, value, reason, created_at, updated_at)
VALUES ('user_agent', 'BadBot', 'Malicious crawler', NOW(), NOW());
```

### Temporary Block (Expires After 24 Hours)

```sql
INSERT INTO blocked_accesses (type, value, reason, expires_at, created_at, updated_at)
VALUES ('ip', '5.6.7.8', 'Suspicious activity', DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW(), NOW());
```

### Remove a Block

```sql
DELETE FROM blocked_accesses WHERE value = '1.2.3.4';
```

### View All Active Blocks

```sql
SELECT * FROM blocked_accesses 
WHERE expires_at IS NULL OR expires_at > NOW()
ORDER BY created_at DESC;
```

## CIDR Notation Examples

Common network ranges for blocking:

### Private Networks
```
10.0.0.0/8          - Entire 10.x.x.x range
172.16.0.0/12       - 172.16.x.x through 172.31.x.x
192.168.0.0/16      - Entire 192.168.x.x range
```

### IPv6 Examples
```
2001:db8::/32       - IPv6 documentation range
fe80::/10           - IPv6 link-local addresses
```

### Common Subnet Sizes
```
/32 - Single IPv4 address (255.255.255.255)
/24 - 256 addresses (255.255.255.0) - e.g., 192.168.1.0/24
/16 - 65,536 addresses (255.255.0.0) - e.g., 192.168.0.0/16
/8  - 16,777,216 addresses (255.0.0.0) - e.g., 10.0.0.0/8
```

## Cache Behavior

- **Cache Key**: `blocklist:all`
- **TTL**: 60 seconds (1 minute)
- **Structure**:
  ```php
  [
      'ip' => ['1.2.3.4', '5.6.7.8'],
      'ip_range' => ['10.0.0.0/8', '192.168.1.0/24'],
      'user_agent' => ['BadBot', 'Scraper']
  ]
  ```

### Cache Refresh

Blocks take effect within 60 seconds due to the cache TTL. For immediate effect:

```php
use Illuminate\Support\Facades\Cache;

Cache::forget('blocklist:all');
```

Or via Artisan:

```bash
php artisan cache:forget blocklist:all
```

## Response Behavior

Blocked requests receive a minimal JSON response:

```json
{
    "message": "Forbidden"
}
```

HTTP Status: `403 Forbidden`

No additional details are disclosed to avoid information leakage about blocking rules.

## Middleware Execution Order

The `BlockAccessMiddleware` runs early in the request lifecycle:

1. `TrustProxies` - Determines real client IP
2. `ValidatePostSize` - Validates request size
3. **`BlockAccessMiddleware`** - Checks blocklist ← **Blocks here**
4. `EncryptCookies` - Cookie encryption
5. Authentication middleware - Never reached if blocked
6. Application logic - Never reached if blocked

This ensures minimal resource consumption for blocked requests.

## Performance Considerations

- Blocklist cached in Redis (1-minute TTL)
- Database only queried on cache miss or first request after TTL expiry
- Typical blocklist size < 100 entries
- Middleware adds < 1ms overhead per request (cache hit)
- Expired blocks automatically filtered before caching

## Proxy Support

The middleware uses Laravel's `$request->ip()` method, which automatically respects the `TrustProxies` middleware configuration. Ensure `config/trustedproxy.php` is properly configured for your infrastructure.

## Testing Blocks

Always test new blocks in staging before applying to production:

```sql
-- Check if an IP would be blocked
SELECT * FROM blocked_accesses 
WHERE (type = 'ip' AND value = '1.2.3.4')
   OR (type = 'ip_range' AND '1.2.3.4' BETWEEN INET_ATON(SUBSTRING_INDEX(value, '/', 1)) AND INET_ATON(SUBSTRING_INDEX(value, '/', 1)))
   AND (expires_at IS NULL OR expires_at > NOW());
```

## Troubleshooting

### Legitimate Users Being Blocked

1. Check for overly broad CIDR ranges
2. Verify user agent patterns aren't too generic
3. Check for shared IPs (corporate VPNs, public WiFi)
4. Remove or expire the problematic block

### Blocks Not Taking Effect

1. Wait up to 60 seconds for cache to expire
2. Manually flush cache: `Cache::forget('blocklist:all')`
3. Verify block is in database and not expired
4. Check middleware is registered in `bootstrap/app.php`

### Performance Issues

1. Keep blocklist small (< 100 entries recommended)
2. Use expiration for temporary blocks
3. Clean up old expired entries periodically
4. Consider moving permanent blocks to infrastructure level (nginx, CloudFlare)
