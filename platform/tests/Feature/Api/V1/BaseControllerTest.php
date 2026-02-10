<?php

use App\Enums\BaseStatus;
use App\Enums\Weight;
use App\Models\Account;
use App\Models\Base;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/bases')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $base = Base::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/bases/{$base->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $this->postJson('/api/v1/bases', [
        'descriptor' => 'Test Base',
        'status' => BaseStatus::Active->value,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $base = Base::factory()->create(['account_id' => $account->id]);
    $this->patchJson("/api/v1/bases/{$base->id}", [
        'descriptor' => 'Updated',
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $base = Base::factory()->create(['account_id' => $account->id]);
    $this->deleteJson("/api/v1/bases/{$base->id}")
        ->assertStatus(401);
});

test('index returns paginated bases scoped to account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Base::factory()->count(3)->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/bases', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'descriptor', 'description', 'code', 'status', 'created_at', 'updated_at']);
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return bases from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Base::factory()->count(2)->create(['account_id' => $accountA->id]);
    Base::factory()->count(3)->create(['account_id' => $accountB->id]);
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/bases', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    $items = $data['data'] ?? $data;
    expect(count($items))->toBe(2);
});

test('show returns single base with inventories', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/bases/{$base->id}", withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id', 'descriptor', 'description', 'code', 'status', 'weight', 'size',
            'created_at', 'updated_at', 'inventories',
        ],
    ]);
    expect($response->json('data.id'))->toBe($base->id);
});

test('show for another account base returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/bases/{$baseA->id}", withBearer($token));

    $response->assertStatus(403);
});

test('show for non-existent base returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/bases/999999', withBearer($token));

    $response->assertStatus(404);
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates base with account_id', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'descriptor' => 'API Test Base',
        'status' => BaseStatus::Active->value,
        'weight' => Weight::Worsted->value,
    ];

    $response = $this->postJson('/api/v1/bases', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id', 'descriptor', 'description', 'code', 'status', 'weight',
            'created_at', 'updated_at',
        ],
    ]);
    $response->assertJsonPath('data.descriptor', 'API Test Base');
    $this->assertDatabaseHas('bases', [
        'account_id' => $account->id,
        'descriptor' => 'API Test Base',
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/bases', [
        'descriptor' => '',
        'status' => 'invalid_status',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies base', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create([
        'account_id' => $account->id,
        'descriptor' => 'Original Descriptor',
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/bases/{$base->id}", [
        'descriptor' => 'Updated Descriptor',
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.descriptor', 'Updated Descriptor');
    $this->assertDatabaseHas('bases', [
        'id' => $base->id,
        'descriptor' => 'Updated Descriptor',
    ]);
});

test('update for another account base returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/bases/{$baseA->id}", [
        'descriptor' => 'Hacked Descriptor',
    ], withBearer($token));

    $response->assertStatus(403);
    $this->assertDatabaseHas('bases', [
        'id' => $baseA->id,
        'descriptor' => $baseA->descriptor,
    ]);
});

test('update with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/bases/{$base->id}", [
        'status' => 'invalid_status',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('destroy soft-deletes base', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/bases/{$base->id}", [], withBearer($token));

    $response->assertStatus(204);
    $this->assertSoftDeleted('bases', ['id' => $base->id]);
});

test('destroy for another account base returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/bases/{$baseA->id}", [], withBearer($token));

    $response->assertStatus(403);
    expect(Base::find($baseA->id))->not->toBeNull();
});
