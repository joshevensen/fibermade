<?php

use App\Models\User;

test('ValidationException returns 422 with message and errors', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/_test/validate', [], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors' => ['required_field']]);
    $response->assertJsonPath('errors.required_field', fn ($v) => count($v) > 0);
});

test('ModelNotFoundException returns 404 with resource not found message', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/model/999999', withBearer($token));

    $response->assertStatus(404);
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('AuthenticationException returns 401 with unauthenticated message', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(401);
    $response->assertExactJson(['message' => 'Unauthenticated.']);
});

test('AuthorizationException returns 403 with forbidden message', function () {
    $user = User::factory()->create(['account_id' => null, 'is_admin' => false]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/authorize', withBearer($token));

    $response->assertStatus(403);
    $response->assertExactJson(['message' => 'Forbidden.']);
});

test('generic exception returns 500 with server error message when not in debug', function () {
    config(['app.debug' => false]);

    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/server-error', withBearer($token));

    $response->assertStatus(500);
    $response->assertExactJson(['message' => 'Server error.']);
});

test('rate limit headers are present in API responses', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/health', withBearer($token));

    $response->assertStatus(200);
    $response->assertHeader('X-RateLimit-Limit');
    $response->assertHeader('X-RateLimit-Remaining');
});

test('web routes return HTML error pages not JSON', function () {
    $response = $this->get('/nonexistent-page-that-does-not-exist');

    $response->assertStatus(404);
    expect(str_contains($response->headers->get('Content-Type', ''), 'text/html'))->toBeTrue();
});
