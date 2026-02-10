<?php

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;

test('unauthenticated index returns 401', function () {
    $this->getJson('/api/v1/customers')
        ->assertStatus(401);
});

test('unauthenticated show returns 401', function () {
    $account = Account::factory()->create();
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/customers/{$customer->id}")
        ->assertStatus(401);
});

test('unauthenticated store returns 401', function () {
    $account = Account::factory()->create();
    $this->postJson('/api/v1/customers', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ])->assertStatus(401);
});

test('unauthenticated update returns 401', function () {
    $account = Account::factory()->create();
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'name' => 'Updated Name',
    ])->assertStatus(401);
});

test('unauthenticated destroy returns 401', function () {
    $account = Account::factory()->create();
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $this->deleteJson("/api/v1/customers/{$customer->id}")
        ->assertStatus(401);
});

test('index returns paginated customers scoped to account', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    Customer::factory()->count(3)->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/customers', withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray();
    $items = $data['data'] ?? $data;
    expect($items)->toBeArray()
        ->and(count($items))->toBe(3);
    expect($items[0])->toHaveKeys(['id', 'name', 'email', 'created_at', 'updated_at']);
    if (isset($data['links'])) {
        expect($data)->toHaveKeys(['links', 'meta']);
    }
});

test('index does not return customers from other accounts', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userA = User::factory()->create(['account_id' => $accountA->id]);
    Customer::factory()->count(2)->create(['account_id' => $accountA->id]);
    Customer::factory()->count(3)->create(['account_id' => $accountB->id]);
    $token = getApiToken($userA);

    $response = $this->getJson('/api/v1/customers', withBearer($token));

    $response->assertStatus(200);
    $items = $response->json('data.data') ?? $response->json('data');
    expect($items)->toBeArray()
        ->and(count($items))->toBe(2);
});

test('show returns customer with orders loaded', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/customers/{$customer->id}", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toHaveKeys(['id', 'name', 'orders']);
    expect($data['orders'])->toBeArray();
});

test('show for another account customer returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $customerA = Customer::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/customers/{$customerA->id}", withBearer($token));

    $response->assertForbidden();
});

test('show for non-existent customer returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/customers/999999', withBearer($token));

    $response->assertNotFound();
    $response->assertExactJson(['message' => 'Resource not found.']);
});

test('store creates customer with account_id from authenticated user', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);
    $payload = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '555-1234',
    ];

    $response = $this->postJson('/api/v1/customers', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.name', 'Jane Doe');
    $response->assertJsonPath('data.email', 'jane@example.com');
    $this->assertDatabaseHas('customers', [
        'account_id' => $account->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

test('store with invalid data returns 422', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/customers', [
        'name' => '',
        'email' => 'not-an-email',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('update modifies customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Name',
    ]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/customers/{$customer->id}", [
        'name' => 'Updated Name',
    ], withBearer($token));

    $response->assertStatus(200);
    $response->assertJsonPath('data.name', 'Updated Name');
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Name',
    ]);
});

test('update for another account customer returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $customerA = Customer::factory()->create([
        'account_id' => $accountA->id,
        'name' => 'Original',
    ]);
    $token = getApiToken($userB);

    $response = $this->patchJson("/api/v1/customers/{$customerA->id}", [
        'name' => 'Hacked',
    ], withBearer($token));

    $response->assertForbidden();
    $this->assertDatabaseHas('customers', [
        'id' => $customerA->id,
        'name' => 'Original',
    ]);
});

test('update with invalid data returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->patchJson("/api/v1/customers/{$customer->id}", [
        'email' => 'not-an-email',
    ], withBearer($token));

    $response->assertStatus(422);
    $response->assertJsonStructure(['message', 'errors']);
});

test('destroy soft-deletes customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->deleteJson("/api/v1/customers/{$customer->id}", [], withBearer($token));

    $response->assertStatus(204);
    expect(Customer::find($customer->id))->toBeNull();
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

test('destroy for another account customer returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $customerA = Customer::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->deleteJson("/api/v1/customers/{$customerA->id}", [], withBearer($token));

    $response->assertForbidden();
    expect(Customer::find($customerA->id))->not->toBeNull();
});
