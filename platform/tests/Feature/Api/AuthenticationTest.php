<?php

use App\Models\User;

test('health route returns 200 with valid bearer token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->getJson('/api/v1/health', [
        'Authorization' => 'Bearer '.$token,
    ]);

    $response->assertStatus(200);
    $response->assertExactJson(['status' => 'ok']);
});

test('health route returns 401 without token', function () {
    $response = $this->getJson('/api/v1/health');

    $response->assertStatus(401);
});

test('health route returns 401 with invalid token', function () {
    $response = $this->getJson('/api/v1/health', [
        'Authorization' => 'Bearer invalid-token',
    ]);

    $response->assertStatus(401);
});

test('api:create-token creates token and outputs it', function () {
    $user = User::factory()->create();

    $this->artisan('api:create-token', ['email' => $user->email])
        ->assertSuccessful();

    expect($user->tokens()->count())->toBe(1);
});

test('api:create-token fails gracefully for unknown email', function () {
    $this->artisan('api:create-token', ['email' => 'nobody@example.com'])
        ->assertFailed();
});
