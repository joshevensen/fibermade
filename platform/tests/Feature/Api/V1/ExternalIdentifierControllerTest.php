<?php

use App\Models\Account;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/external-identifiers?integration_id=1&external_type=product&external_id=123')
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $account = Account::factory()->create();
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $this->postJson('/api/v1/external-identifiers', [
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/123',
    ])->assertStatus(401);
});

test('index requires integration_id', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/external-identifiers', withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['integration_id']);
});

test('index returns identifiers by external_type and external_id', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $identifier = ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/456',
        'data' => ['admin_url' => 'https://admin.shopify.com/'],
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/external-identifiers?integration_id={$integration->id}&external_type=product&external_id=gid%3A%2F%2Fshopify%2FProduct%2F456", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and(count($data))->toBe(1);
    expect($data[0]['external_id'])->toBe('gid://shopify/Product/456');
    expect($data[0]['identifiable_type'])->toBe(Colorway::class);
});

test('index returns identifiers by identifiable_type and identifiable_id', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/789',
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/external-identifiers?integration_id={$integration->id}&identifiable_type=colorway&identifiable_id={$colorway->id}", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and(count($data))->toBe(1);
    expect($data[0]['identifiable_id'])->toBe($colorway->id);
});

test('index for another account integration returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $integrationA = Integration::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/external-identifiers?integration_id={$integrationA->id}&external_type=product&external_id=123", withBearer($token));

    $response->assertForbidden();
});

test('index without external_type and external_id or identifiable_type and identifiable_id returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/external-identifiers?integration_id={$integration->id}", withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonPath('errors.query.0', 'Provide either (integration_id, external_type, external_id) or (integration_id, identifiable_type, identifiable_id).');
});

test('index with invalid integration_id returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/external-identifiers?integration_id=999999&external_type=product&external_id=123', withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['integration_id']);
});

test('store creates external identifier', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'integration_id' => $integration->id,
        'identifiable_type' => 'colorway',
        'identifiable_id' => $colorway->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/999',
        'data' => ['admin_url' => 'https://example.com'],
    ];

    $response = $this->postJson('/api/v1/external-identifiers', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.external_type', 'product');
    $response->assertJsonPath('data.external_id', 'gid://shopify/Product/999');
    $response->assertJsonPath('data.identifiable_type', Colorway::class);
    $this->assertDatabaseHas('external_identifiers', [
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/999',
    ]);
});

test('store with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/external-identifiers', [
        'integration_id' => $integration->id,
        'identifiable_type' => 'invalid',
        'identifiable_id' => 1,
        'external_type' => 'product',
        'external_id' => 'abc',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
    $response->assertJsonValidationErrors(['identifiable_type']);
});

test('store for another account integration returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $integrationA = Integration::factory()->create(['account_id' => $accountA->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->postJson('/api/v1/external-identifiers', [
        'integration_id' => $integrationA->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorwayA->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/hack',
    ], withBearer($token));

    $response->assertForbidden();
    $this->assertDatabaseMissing('external_identifiers', [
        'external_id' => 'gid://shopify/Product/hack',
    ]);
});

test('store rejects duplicate external_id same integration and external_type', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $colorway1 = Colorway::factory()->create(['account_id' => $account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $account->id]);
    ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway1->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/dup',
    ]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/external-identifiers', [
        'integration_id' => $integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway2->id,
        'external_type' => 'product',
        'external_id' => 'gid://shopify/Product/dup',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['external_id']);
    $count = ExternalIdentifier::where('integration_id', $integration->id)
        ->where('external_type', 'product')
        ->where('external_id', 'gid://shopify/Product/dup')
        ->count();
    expect($count)->toBe(1);
});
