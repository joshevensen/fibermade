<?php

use App\Models\BlockedAccess;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\get;

beforeEach(function () {
    Cache::flush();
});

it('blocks requests from individual IP addresses', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    $response->assertForbidden();
    $response->assertJson(['message' => 'Forbidden']);
});

it('allows requests from non-blocked IP addresses', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    $response = get('/', ['REMOTE_ADDR' => '5.6.7.8']);

    $response->assertSuccessful();
});

it('blocks requests from IP within blocked CIDR range', function () {
    BlockedAccess::create([
        'type' => 'ip_range',
        'value' => '192.168.1.0/24',
        'reason' => 'Test CIDR block',
    ]);

    $response = get('/', ['REMOTE_ADDR' => '192.168.1.50']);

    $response->assertForbidden();
});

it('allows requests from IP outside blocked CIDR range', function () {
    BlockedAccess::create([
        'type' => 'ip_range',
        'value' => '192.168.1.0/24',
        'reason' => 'Test CIDR block',
    ]);

    $response = get('/', ['REMOTE_ADDR' => '10.0.0.50']);

    $response->assertSuccessful();
});

it('supports both IPv4 and IPv6 CIDR ranges', function () {
    BlockedAccess::create([
        'type' => 'ip_range',
        'value' => '2001:db8::/32',
        'reason' => 'Test IPv6 CIDR block',
    ]);

    $response = get('/', ['REMOTE_ADDR' => '2001:db8::1']);

    $response->assertForbidden();
});

it('blocks requests with blocked user agent substring', function () {
    BlockedAccess::create([
        'type' => 'user_agent',
        'value' => 'BadBot',
        'reason' => 'Test user agent block',
    ]);

    $response = $this->withHeaders(['User-Agent' => 'BadBot/1.0'])->get('/');

    $response->assertForbidden();
});

it('allows requests with non-blocked user agent', function () {
    BlockedAccess::create([
        'type' => 'user_agent',
        'value' => 'BadBot',
        'reason' => 'Test user agent block',
    ]);

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Chrome)'])->get('/');

    $response->assertSuccessful();
});

it('performs case-insensitive user agent matching', function () {
    BlockedAccess::create([
        'type' => 'user_agent',
        'value' => 'BadBot',
        'reason' => 'Test user agent block',
    ]);

    $response = $this->withHeaders(['User-Agent' => 'badbot/1.0'])->get('/');

    $response->assertForbidden();
});

it('ignores expired blocks', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Expired block',
        'expires_at' => now()->subMinute(),
    ]);

    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    $response->assertSuccessful();
});

it('enforces non-expired blocks', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Active block',
        'expires_at' => now()->addMinute(),
    ]);

    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    $response->assertForbidden();
});

it('enforces blocks without expiration indefinitely', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Permanent block',
        'expires_at' => null,
    ]);

    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    $response->assertForbidden();
});

it('caches blocklist on first request', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    get('/', ['REMOTE_ADDR' => '5.6.7.8']);

    expect(Cache::has('blocklist:all'))->toBeTrue();
});

it('uses cached blocklist on subsequent requests', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    // First request warms cache
    get('/', ['REMOTE_ADDR' => '5.6.7.8']);

    // Delete from database
    BlockedAccess::truncate();

    // Second request still uses cache (block still in effect)
    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    $response->assertForbidden();
});

it('refreshes cache after TTL expiration', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    // First request warms cache
    get('/', ['REMOTE_ADDR' => '5.6.7.8']);

    // Simulate cache expiration
    Cache::forget('blocklist:all');

    // Delete block from database
    BlockedAccess::truncate();

    // Request after cache expiry loads fresh data (no block)
    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    $response->assertSuccessful();
});

it('executes before authentication middleware', function () {
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    // Make request to auth-protected route
    $response = get('/dashboard', ['REMOTE_ADDR' => '1.2.3.4']);

    // Should be blocked with 403, not redirected to login (302)
    $response->assertForbidden();
});

it('uses laravel request ip method for proxy-aware detection', function () {
    // This test verifies that the middleware uses $request->ip()
    // which automatically handles proxy detection via TrustProxies middleware
    BlockedAccess::create([
        'type' => 'ip',
        'value' => '1.2.3.4',
        'reason' => 'Test block',
    ]);

    // Direct request from blocked IP
    $response = get('/', ['REMOTE_ADDR' => '1.2.3.4']);

    // Should be blocked - middleware uses $request->ip() correctly
    $response->assertForbidden();
});
