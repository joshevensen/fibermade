**Sub-project**: platform

## Why

Fibermade needs protection from bad actors attempting malicious activities (DDoS attacks, brute force login attempts, scraping, abuse). Without a blocking mechanism, the platform is vulnerable to sustained attacks that could impact performance and security for legitimate users. An IP-based blocking system provides immediate defense by preventing identified threat sources from accessing the application.

## What Changes

**Blocking Infrastructure**:
- Add middleware to check incoming requests against a blocklist of IP addresses and other identifiable criteria
- Create database table to store blocked IP addresses, user agents, and block metadata (reason, expiration)
- Implement graceful block responses (403 Forbidden with minimal information disclosure)
- Cache blocklist with short TTL (1-5 minutes) for performance while allowing quick updates

**Manual Management**:
- Managed via direct database inserts/updates (TablePlus, CLI, etc.)
- Support blocking by IP address (individual or CIDR ranges)
- Support blocking by user agent patterns
- Allow optional block expiration (temporary blocks)
- No UI required - owner is comfortable with manual database management

## Capabilities

### New Capabilities
- `ip-blocking`: Core blocking mechanism via middleware, .env config, and database-backed blocklist with manual management

### Modified Capabilities
<!-- No existing capabilities are being modified -->

## Impact

**Platform (Laravel)**:
- New `BlockedAccess` model and migration: Store blocked IPs, user agents, reasons, expiration dates
- New `BlockAccessMiddleware`: Early-stage middleware to check requests against database blocklist
- `Kernel.php`: Register BlockAccessMiddleware in global or `web`/`api` middleware groups

**Database**:
- New `blocked_accesses` table:
  - `id`, `type` (ip, user_agent, ip_range)
  - `value` (IP address, CIDR range, or user agent pattern)
  - `reason` (text explaining why blocked)
  - `expires_at` (nullable datetime for temporary blocks)
  - `created_at`, `updated_at`
  - Indexes on `type`, `value`, and `expires_at` for fast lookup
- Manual management via direct database inserts/updates (TablePlus, CLI, etc.)

**Security Considerations**:
- Early middleware execution to minimize resource consumption from blocked requests
- Cache blocklist with short TTL (1 minute) to balance performance with quick updates during attacks
- Avoid information disclosure in block responses (simple 403, no details)
- Automatic expiration handling (expired blocks are ignored by middleware)

**Dependencies**:
- Laravel's request object for IP detection (`$request->ip()`)
- Laravel's middleware system
- Cache system (Redis/Memcached) for blocklist caching with short TTL
