### Requirement: Block requests from individual IP addresses
The system SHALL block all HTTP requests originating from IP addresses explicitly listed in the blocklist.

#### Scenario: Request from blocked IP address
- **WHEN** a request arrives from an IP address that exists in the blocklist with type 'ip'
- **THEN** the system SHALL return HTTP 403 Forbidden
- **AND** the request SHALL NOT proceed to any further middleware or application logic

#### Scenario: Request from non-blocked IP address
- **WHEN** a request arrives from an IP address that does not exist in the blocklist
- **THEN** the system SHALL allow the request to proceed normally

### Requirement: Block requests from IP address ranges (CIDR notation)
The system SHALL block all HTTP requests originating from IP addresses within CIDR ranges listed in the blocklist.

#### Scenario: Request from IP within blocked CIDR range
- **WHEN** a request arrives from IP address 192.168.1.50
- **AND** the blocklist contains entry type 'ip_range' with value '192.168.1.0/24'
- **THEN** the system SHALL return HTTP 403 Forbidden

#### Scenario: Request from IP outside blocked CIDR range
- **WHEN** a request arrives from IP address 10.0.0.50
- **AND** the blocklist contains entry type 'ip_range' with value '192.168.1.0/24'
- **THEN** the system SHALL allow the request to proceed normally

#### Scenario: IPv4 and IPv6 CIDR support
- **WHEN** the blocklist contains CIDR ranges in both IPv4 (e.g., '192.168.0.0/16') and IPv6 (e.g., '2001:db8::/32') formats
- **THEN** the system SHALL correctly match client IPs against both IPv4 and IPv6 ranges

### Requirement: Block requests by User-Agent pattern
The system SHALL block all HTTP requests whose User-Agent header contains blocked user agent patterns.

#### Scenario: Request with blocked User-Agent substring
- **WHEN** a request arrives with User-Agent header 'BadBot/1.0'
- **AND** the blocklist contains entry type 'user_agent' with value 'BadBot'
- **THEN** the system SHALL return HTTP 403 Forbidden

#### Scenario: Request with non-blocked User-Agent
- **WHEN** a request arrives with User-Agent header 'Mozilla/5.0 (Chrome)'
- **AND** the blocklist contains entry type 'user_agent' with value 'BadBot'
- **THEN** the system SHALL allow the request to proceed normally

#### Scenario: Case-insensitive User-Agent matching
- **WHEN** a request arrives with User-Agent header 'badbot/1.0' (lowercase)
- **AND** the blocklist contains entry type 'user_agent' with value 'BadBot' (mixed case)
- **THEN** the system SHALL return HTTP 403 Forbidden

### Requirement: Respect block expiration timestamps
The system SHALL automatically ignore blocklist entries that have expired based on their expiration timestamp.

#### Scenario: Active block (no expiration)
- **WHEN** a blocklist entry has expires_at NULL
- **THEN** the system SHALL enforce the block indefinitely

#### Scenario: Active block (not yet expired)
- **WHEN** a blocklist entry has expires_at set to a future datetime
- **THEN** the system SHALL enforce the block

#### Scenario: Expired block
- **WHEN** a blocklist entry has expires_at set to a past datetime
- **THEN** the system SHALL ignore the block
- **AND** requests matching that entry SHALL proceed normally

### Requirement: Execute blocking check early in request lifecycle
The system SHALL check blocklist status before executing authentication, session, or application-level middleware.

#### Scenario: Blocked request does not reach authentication
- **WHEN** a blocked request arrives
- **THEN** the system SHALL return 403 before checking authentication status
- **AND** no session data SHALL be accessed
- **AND** no database queries beyond blocklist check SHALL execute

### Requirement: Cache blocklist for performance
The system SHALL cache the active blocklist in memory (Redis) to minimize database queries on every request.

#### Scenario: Blocklist cache hit
- **WHEN** the blocklist is cached in Redis
- **THEN** the system SHALL read from cache
- **AND** no database query SHALL be executed for blocklist lookup

#### Scenario: Blocklist cache miss
- **WHEN** the blocklist is not cached in Redis
- **THEN** the system SHALL query the database for active blocks
- **AND** store the result in Redis cache

#### Scenario: Cache refresh after expiration
- **WHEN** the blocklist cache TTL expires (after 1 minute)
- **THEN** the next request SHALL trigger a database query to refresh the cache
- **AND** subsequent requests SHALL use the refreshed cache

### Requirement: Return minimal error response for blocked requests
The system SHALL return a generic 403 Forbidden response without disclosing blocking rules or reasons.

#### Scenario: Blocked request receives minimal response
- **WHEN** a request is blocked
- **THEN** the system SHALL return HTTP status 403
- **AND** the response body SHALL contain JSON: `{"message": "Forbidden"}`
- **AND** no additional details about the block SHALL be disclosed

### Requirement: Support manual blocklist management via database
The system SHALL allow blocklist entries to be created, updated, and deleted via direct database operations.

#### Scenario: Create new block via database insert
- **WHEN** a new row is inserted into blocked_accesses table
- **THEN** the block SHALL take effect within the cache TTL period (1 minute)

#### Scenario: Remove block via database delete
- **WHEN** a row is deleted from blocked_accesses table
- **THEN** the block SHALL be lifted within the cache TTL period (1 minute)

#### Scenario: Update block via database update
- **WHEN** a row is updated in blocked_accesses table (e.g., changing expires_at)
- **THEN** the updated block SHALL take effect within the cache TTL period (1 minute)

### Requirement: Handle multiple block types simultaneously
The system SHALL enforce all active block types (IP, IP range, and User-Agent) on every request.

#### Scenario: Request matches multiple block types
- **WHEN** a request's IP address is in the blocklist
- **AND** the request's User-Agent is also in the blocklist
- **THEN** the system SHALL return 403 (blocking on first match is sufficient)

#### Scenario: Request matches one block type but not others
- **WHEN** a request's IP address is NOT in the blocklist
- **AND** the request's User-Agent IS in the blocklist
- **THEN** the system SHALL return 403

### Requirement: Detect client IP accurately behind proxies
The system SHALL use the correct client IP address when the application is behind a proxy or load balancer.

#### Scenario: Request behind trusted proxy
- **WHEN** the application is configured with trusted proxies
- **AND** a request arrives with X-Forwarded-For header
- **THEN** the system SHALL use the client IP from X-Forwarded-For for blocklist matching
- **AND** the proxy IP SHALL NOT be used for matching

#### Scenario: Request without proxy
- **WHEN** a request arrives directly (not through a proxy)
- **THEN** the system SHALL use the connection's remote address for blocklist matching
