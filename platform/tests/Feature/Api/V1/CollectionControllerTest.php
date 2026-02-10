<?php

use App\Enums\BaseStatus;
use App\Models\Account;
use App\Models\Collection;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/collections')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/collections/{$collection->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $this->postJson('/api/v1/collections', [
        'name' => 'Test',
        'status' => BaseStatus::Active->value,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $this->patchJson("/api/v1/collections/{$collection->id}", [
        'name' => 'Updated',
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $this->deleteJson("/api/v1/collections/{$collection->id}")
        ->assertStatus(401);
});

test('index returns paginated collections scoped to account with colorways_count', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Collection::factory()->count(3)->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/collections', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'name', 'description', 'status', 'created_at', 'updated_at', 'colorways_count']);
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return collections from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Collection::factory()->count(2)->create(['account_id' => $accountA->id]);
    Collection::factory()->count(3)->create(['account_id' => $accountB->id]);
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/collections', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    $items = $data['data'] ?? $data;
    expect(count($items))->toBe(2);
});

test('show returns single collection with colorways', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/collections/{$collection->id}", withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id', 'name', 'description', 'status',
            'created_at', 'updated_at', 'colorways',
        ],
    ]);
    expect($response->json('data.id'))->toBe($collection->id);
});

test('show for another account collection returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $collectionA = Collection::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/collections/{$collectionA->id}", withBearer($token));

    $response->assertStatus(403);
});

test('show for non-existent collection returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/collections/999999', withBearer($token));

    $response->assertStatus(404);
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates collection with account_id', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'name' => 'API Test Collection',
        'status' => BaseStatus::Active->value,
    ];

    $response = $this->postJson('/api/v1/collections', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id', 'name', 'description', 'status',
            'created_at', 'updated_at',
        ],
    ]);
    $response->assertJsonPath('data.name', 'API Test Collection');
    $this->assertDatabaseHas('collections', [
        'account_id' => $account->id,
        'name' => 'API Test Collection',
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/collections', [
        'name' => '',
        'status' => 'invalid_status',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies collection', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $collection = Collection::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Name',
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/collections/{$collection->id}", [
        'name' => 'Updated Name',
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Updated Name');
    $this->assertDatabaseHas('collections', [
        'id' => $collection->id,
        'name' => 'Updated Name',
    ]);
});

test('update for another account collection returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $collectionA = Collection::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/collections/{$collectionA->id}", [
        'name' => 'Hacked Name',
    ], withBearer($token));

    $response->assertStatus(403);
    $this->assertDatabaseHas('collections', [
        'id' => $collectionA->id,
        'name' => $collectionA->name,
    ]);
});

test('update with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/collections/{$collection->id}", [
        'status' => 'invalid_status',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('destroy soft-deletes collection', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/collections/{$collection->id}", [], withBearer($token));

    $response->assertStatus(204);
    $this->assertSoftDeleted('collections', ['id' => $collection->id]);
});

test('destroy for another account collection returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $collectionA = Collection::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/collections/{$collectionA->id}", [], withBearer($token));

    $response->assertStatus(403);
    expect(Collection::find($collectionA->id))->not->toBeNull();
});
