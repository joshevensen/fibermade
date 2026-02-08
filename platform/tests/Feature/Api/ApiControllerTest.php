<?php

use App\Models\Account;
use App\Models\Base;
use App\Models\User;

test('successResponse returns 200 with data envelope', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/success', withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['key']]);
    $response->assertExactJson(['data' => ['key' => 'value']]);
});

test('createdResponse returns 201 with data envelope', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/created', withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonStructure(['data' => ['id']]);
    $response->assertExactJson(['data' => ['id' => 1]]);
});

test('errorResponse returns 422 with message and errors', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/error', withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors' => ['field']]);
    $response->assertExactJson([
        'message' => 'Validation failed',
        'errors' => ['field' => ['Error message']],
    ]);
});

test('notFoundResponse returns 404 with message', function () {
    $user = User::factory()->create();
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/not-found', withBearer($token));

    $response->assertStatus(404);
    $response->assertJsonStructure(['message']);
    $response->assertExactJson(['message' => 'Resource not found']);
});

test('accountId returns authenticated user account_id', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/account-id', withBearer($token));

    $response->assertStatus(200);
    $response->assertExactJson(['data' => ['account_id' => $account->id]]);
});

test('scopeToAccount scopes query for regular user', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    Base::factory()->create(['account_id' => $accountA->id]);
    Base::factory()->create(['account_id' => $accountB->id]);

    $user = User::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/scope', withBearer($token));

    $response->assertStatus(200);
    $response->assertExactJson(['data' => ['count' => 1]]);
});

test('scopeToAccount bypasses scoping for admin user', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    Base::factory()->create(['account_id' => $accountA->id]);
    Base::factory()->create(['account_id' => $accountB->id]);

    $user = User::factory()->create(['account_id' => $accountA->id, 'is_admin' => true]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/_test/scope', withBearer($token));

    $response->assertStatus(200);
    $response->assertExactJson(['data' => ['count' => 2]]);
});
