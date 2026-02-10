<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Integration;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/integrations')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/integrations/{$integration->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $account = Account::factory()->create();
    $this->postJson('/api/v1/integrations', [
        'type' => IntegrationType::Shopify->value,
        'credentials' => 'secret',
        'active' => true,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $this->patchJson("/api/v1/integrations/{$integration->id}", [
        'active' => false,
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $this->deleteJson("/api/v1/integrations/{$integration->id}")
        ->assertStatus(401);
});

test('index returns paginated integrations scoped to account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Integration::factory()->count(3)->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/integrations', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'type', 'settings', 'active', 'created_at', 'updated_at'])
        ->and($items[0])->not->toHaveKey('credentials');
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return integrations from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Integration::factory()->count(2)->create(['account_id' => $accountA->id]);
    Integration::factory()->count(3)->create(['account_id' => $accountB->id]);
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/integrations', withBearer($token));

    $response->assertStatus(200);
    $items = $response->json('data.data') ?? $response->json('data');
    expect($items)->toBeArray()
        ->and(count($items))->toBe(2);
});

test('show returns integration with logs and never includes credentials', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'credentials' => 'super-secret-token',
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/integrations/{$integration->id}", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveKeys(['id', 'type', 'settings', 'active', 'logs'])
        ->and($data)->not->toHaveKey('credentials');
});

test('show for another account integration returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $integrationA = Integration::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/integrations/{$integrationA->id}", withBearer($token));

    $response->assertForbidden();
});

test('show for non-existent integration returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/integrations/999999', withBearer($token));

    $response->assertNotFound();
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates integration with account_id from authenticated user and response excludes credentials', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'type' => IntegrationType::Shopify->value,
        'credentials' => 'encrypted-api-secret',
        'settings' => ['store_url' => 'https://mystore.myshopify.com'],
        'active' => true,
    ];

    $response = $this->postJson('/api/v1/integrations', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.type', IntegrationType::Shopify->value);
    $response->assertJsonPath('data.active', true);
    $response->assertJsonMissingPath('data.credentials');
    expect($response->json('data'))->not->toHaveKey('credentials');
    $this->assertDatabaseHas('integrations', [
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify->value,
        'active' => true,
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/integrations', [
        'type' => 'invalid',
        'active' => true,
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies integration and response excludes credentials', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'active' => true,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/integrations/{$integration->id}", [
        'active' => false,
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.active', false);
    expect($response->json('data'))->not->toHaveKey('credentials');
    $this->assertDatabaseHas('integrations', [
        'id' => $integration->id,
        'active' => false,
    ]);
});

test('update for another account integration returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $integrationA = Integration::factory()->create([
        'account_id' => $accountA->id,
        'active' => true,
    ]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/integrations/{$integrationA->id}", [
        'active' => false,
    ], withBearer($token));

    $response->assertForbidden();
    $this->assertDatabaseHas('integrations', [
        'id' => $integrationA->id,
        'active' => true,
    ]);
});

test('destroy soft-deletes integration', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/integrations/{$integration->id}", [], withBearer($token));

    $response->assertStatus(204);
    expect(Integration::find($integration->id))->toBeNull();
    $this->assertSoftDeleted('integrations', ['id' => $integration->id]);
});

test('destroy for another account integration returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $integrationA = Integration::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/integrations/{$integrationA->id}", [], withBearer($token));

    $response->assertForbidden();
    expect(Integration::find($integrationA->id))->not->toBeNull();
});
