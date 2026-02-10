<?php

use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Inventory;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/inventory')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->getJson("/api/v1/inventory/{$inventory->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $this->postJson('/api/v1/inventory', [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->patchJson("/api/v1/inventory/{$inventory->id}", [
        'quantity' => 5,
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->deleteJson("/api/v1/inventory/{$inventory->id}")
        ->assertStatus(401);
});

test('unauthenticated update quantity returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $this->patchJson("/api/v1/inventory/{$inventory->id}/quantity", [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 20,
    ])->assertStatus(401);
});

test('index returns paginated inventory scoped to account with colorway and base', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorways = Colorway::factory()->count(3)->create(['account_id' => $account->id]);
    $bases = Base::factory()->count(3)->create(['account_id' => $account->id]);
    foreach ($colorways as $i => $colorway) {
        Inventory::factory()->create([
            'account_id' => $account->id,
            'colorway_id' => $colorway->id,
            'base_id' => $bases[$i]->id,
        ]);
    }
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/inventory', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'colorway_id', 'base_id', 'quantity', 'created_at', 'updated_at', 'colorway', 'base']);
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return inventory from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    $colorwaysA = Colorway::factory()->count(2)->create(['account_id' => $accountA->id]);
    $basesA = Base::factory()->count(2)->create(['account_id' => $accountA->id]);
    $colorwaysB = Colorway::factory()->count(3)->create(['account_id' => $accountB->id]);
    $basesB = Base::factory()->count(3)->create(['account_id' => $accountB->id]);
    foreach ($colorwaysA as $i => $colorway) {
        Inventory::factory()->create([
            'account_id' => $accountA->id,
            'colorway_id' => $colorway->id,
            'base_id' => $basesA[$i]->id,
        ]);
    }
    foreach ($colorwaysB as $i => $colorway) {
        Inventory::factory()->create([
            'account_id' => $accountB->id,
            'colorway_id' => $colorway->id,
            'base_id' => $basesB[$i]->id,
        ]);
    }
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/inventory', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    $items = $data['data'] ?? $data;
    expect(count($items))->toBe(2);
});

test('show returns single inventory with colorway and base', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/inventory/{$inventory->id}", withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id', 'colorway_id', 'base_id', 'quantity',
            'created_at', 'updated_at', 'colorway', 'base',
        ],
    ]);
    expect($response->json('data.id'))->toBe($inventory->id);
});

test('show for another account inventory returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $inventoryA = Inventory::factory()->create([
        'account_id' => $accountA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
    ]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/inventory/{$inventoryA->id}", withBearer($token));

    $response->assertStatus(403);
});

test('show for non-existent inventory returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/inventory/999999', withBearer($token));

    $response->assertStatus(404);
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates inventory with account_id', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 25,
    ];

    $response = $this->postJson('/api/v1/inventory', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id', 'colorway_id', 'base_id', 'quantity',
            'created_at', 'updated_at', 'colorway', 'base',
        ],
    ]);
    $response->assertJsonPath('data.quantity', 25);
    $this->assertDatabaseHas('inventories', [
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 25,
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/inventory', [
        'colorway_id' => '',
        'base_id' => '',
        'quantity' => -1,
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies inventory', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/inventory/{$inventory->id}", [
        'quantity' => 50,
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.quantity', 50);
    $this->assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'quantity' => 50,
    ]);
});

test('update for another account inventory returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $inventoryA = Inventory::factory()->create([
        'account_id' => $accountA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
        'quantity' => 10,
    ]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/inventory/{$inventoryA->id}", [
        'quantity' => 99,
    ], withBearer($token));

    $response->assertStatus(403);
    $this->assertDatabaseHas('inventories', [
        'id' => $inventoryA->id,
        'quantity' => 10,
    ]);
});

test('update with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/inventory/{$inventory->id}", [
        'quantity' => -5,
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('destroy removes inventory', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/inventory/{$inventory->id}", [], withBearer($token));

    $response->assertStatus(204);
    $this->assertDatabaseMissing('inventories', ['id' => $inventory->id]);
});

test('destroy for another account inventory returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $inventoryA = Inventory::factory()->create([
        'account_id' => $accountA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
    ]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/inventory/{$inventoryA->id}", [], withBearer($token));

    $response->assertStatus(403);
    expect(Inventory::find($inventoryA->id))->not->toBeNull();
});

test('update quantity updates existing inventory via updateOrCreate', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 10,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/inventory/{$inventory->id}/quantity", [
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 75,
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.quantity', 75);
    $response->assertJsonPath('data.id', $inventory->id);
    $this->assertDatabaseHas('inventories', [
        'id' => $inventory->id,
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
        'quantity' => 75,
    ]);
});

test('update quantity creates new inventory when combination does not exist', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway1 = Colorway::factory()->create(['account_id' => $account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $account->id]);
    $base1 = Base::factory()->create(['account_id' => $account->id]);
    $base2 = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway1->id,
        'base_id' => $base1->id,
        'quantity' => 10,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/inventory/{$inventory->id}/quantity", [
        'colorway_id' => $colorway2->id,
        'base_id' => $base2->id,
        'quantity' => 42,
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.quantity', 42);
    $response->assertJsonPath('data.colorway_id', $colorway2->id);
    $response->assertJsonPath('data.base_id', $base2->id);
    $this->assertDatabaseHas('inventories', [
        'account_id' => $account->id,
        'colorway_id' => $colorway2->id,
        'base_id' => $base2->id,
        'quantity' => 42,
    ]);
});

test('update quantity for another account inventory returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $baseA = Base::factory()->create(['account_id' => $accountA->id]);
    $inventoryA = Inventory::factory()->create([
        'account_id' => $accountA->id,
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
        'quantity' => 10,
    ]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/inventory/{$inventoryA->id}/quantity", [
        'colorway_id' => $colorwayA->id,
        'base_id' => $baseA->id,
        'quantity' => 99,
    ], withBearer($token));

    $response->assertStatus(403);
    $this->assertDatabaseHas('inventories', [
        'id' => $inventoryA->id,
        'quantity' => 10,
    ]);
});

test('update quantity with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create(['account_id' => $account->id]);
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/inventory/{$inventory->id}/quantity", [
        'colorway_id' => 999999,
        'base_id' => 999999,
        'quantity' => -1,
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});
