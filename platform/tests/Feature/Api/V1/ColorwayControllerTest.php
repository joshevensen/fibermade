<?php

use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\Colorway;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/colorways')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/colorways/{$colorway->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $this->postJson('/api/v1/colorways', [
        'name' => 'Test',
        'status' => ColorwayStatus::Active->value,
        'per_pan' => 1,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $this->patchJson("/api/v1/colorways/{$colorway->id}", [
        'name' => 'Updated',
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $this->deleteJson("/api/v1/colorways/{$colorway->id}")
        ->assertStatus(401);
});

test('index returns paginated colorways scoped to account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Colorway::factory()->count(3)->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/colorways', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'name', 'description', 'technique', 'colors', 'per_pan', 'status', 'created_at', 'updated_at']);
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return colorways from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Colorway::factory()->count(2)->create(['account_id' => $accountA->id]);
    Colorway::factory()->count(3)->create(['account_id' => $accountB->id]);
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/colorways', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    $items = $data['data'] ?? $data;
    expect(count($items))->toBe(2);
});

test('show returns single colorway with relationships', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/colorways/{$colorway->id}", withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id', 'name', 'description', 'technique', 'colors', 'per_pan', 'status',
            'created_at', 'updated_at', 'collections', 'inventories', 'primary_image_url',
        ],
    ]);
    expect($response->json('data.id'))->toBe($colorway->id);
});

test('show for another account colorway returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/colorways/{$colorwayA->id}", withBearer($token));

    $response->assertStatus(403);
});

test('show for non-existent colorway returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/colorways/999999', withBearer($token));

    $response->assertStatus(404);
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates colorway with account_id and created_by', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'name' => 'API Test Colorway',
        'status' => ColorwayStatus::Active->value,
        'per_pan' => 3,
        'technique' => Technique::Solid->value,
    ];

    $response = $this->postJson('/api/v1/colorways', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id', 'name', 'description', 'technique', 'colors', 'per_pan', 'status',
            'created_at', 'updated_at',
        ],
    ]);
    $response->assertJsonPath('data.name', 'API Test Colorway');
    $this->assertDatabaseHas('colorways', [
        'account_id' => $account->id,
        'name' => 'API Test Colorway',
        'created_by' => $user->id,
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/colorways', [
        'name' => '',
        'status' => 'invalid_status',
        'per_pan' => 99,
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies colorway and sets updated_by', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Name',
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/colorways/{$colorway->id}", [
        'name' => 'Updated Name',
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Updated Name');
    $this->assertDatabaseHas('colorways', [
        'id' => $colorway->id,
        'name' => 'Updated Name',
        'updated_by' => $user->id,
    ]);
});

test('update for another account colorway returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/colorways/{$colorwayA->id}", [
        'name' => 'Hacked Name',
    ], withBearer($token));

    $response->assertStatus(403);
    $this->assertDatabaseHas('colorways', [
        'id' => $colorwayA->id,
        'name' => $colorwayA->name,
    ]);
});

test('update with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/colorways/{$colorway->id}", [
        'status' => 'invalid_status',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('destroy soft-deletes colorway', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/colorways/{$colorway->id}", [], withBearer($token));

    $response->assertStatus(204);
    $this->assertSoftDeleted('colorways', ['id' => $colorway->id]);
});

test('destroy for another account colorway returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/colorways/{$colorwayA->id}", [], withBearer($token));

    $response->assertStatus(403);
    expect(Colorway::find($colorwayA->id))->not->toBeNull();
});
