## 1. Database

- [x] 1.1 Create migration for blocked_accesses table with columns: id, type (enum: ip/ip_range/user_agent), value, reason, expires_at, timestamps
- [x] 1.2 Add indexes on (type, value) and expires_at columns in migration
- [x] 1.3 Create BlockedAccess model with fillable fields and casts (expires_at as datetime)
- [x] 1.4 Add query scope to BlockedAccess model for filtering active (non-expired) blocks

## 2. Middleware

- [x] 2.1 Create BlockAccessMiddleware class in app/Http/Middleware/
- [x] 2.2 Implement cache loading logic: check Redis for 'blocklist:all' key with 1-minute TTL
- [x] 2.3 Implement database fallback: query active blocks grouped by type when cache misses
- [x] 2.4 Implement IP address blocking: exact match against client IP from $request->ip()
- [x] 2.5 Implement CIDR range blocking using Symfony\Component\HttpFoundation\IpUtils::checkIp()
- [x] 2.6 Implement user agent blocking: case-insensitive substring matching against $request->userAgent()
- [x] 2.7 Return JSON response with 403 status and minimal message when blocked
- [x] 2.8 Register BlockAccessMiddleware in app/Http/Kernel.php global middleware stack after TrustProxies

## 3. Tests

- [x] 3.1 Write feature test for blocking individual IP addresses
- [x] 3.2 Write feature test for blocking CIDR ranges (IPv4 and IPv6)
- [x] 3.3 Write feature test for blocking user agent patterns (case-insensitive)
- [x] 3.4 Write feature test for expired blocks being ignored
- [x] 3.5 Write feature test for non-expired blocks being enforced
- [x] 3.6 Write feature test for requests from non-blocked sources proceeding normally
- [x] 3.7 Write feature test for cache warming on first request
- [x] 3.8 Write feature test for cache expiry and refresh after TTL
- [x] 3.9 Write feature test for middleware execution order (runs after TrustProxies, before auth)
- [x] 3.10 Write feature test for proxy-aware IP detection (X-Forwarded-For handling)

## 4. Documentation

- [x] 4.1 Add SQL examples to README or docs for creating/updating/deleting blocks manually
- [x] 4.2 Document cache key format and TTL configuration
- [x] 4.3 Document CIDR notation examples for common network ranges
