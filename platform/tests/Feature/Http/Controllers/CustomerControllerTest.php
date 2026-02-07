<?php

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;

// TODO: Update tests in Stage 2 when customer write operations are re-enabled

test('user can view customers index', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('customers.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/customers/CustomerIndexPage')
        ->has('customers', 1)
        ->where('customers.0.id', $customer->id)
    );
});

test('user can view a specific customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'name' => 'Test Customer',
    ]);

    $response = $this->actingAs($user)->get(route('customers.show', $customer));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/customers/CustomerEditPage')
        ->where('customer.id', $customer->id)
        ->where('customer.name', 'Test Customer')
    );
});

test('user cannot create a customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'New Customer',
        'email' => 'customer@example.com',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('customers', [
        'name' => 'New Customer',
        'email' => 'customer@example.com',
    ]);
});

test('user cannot update a customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Name',
    ]);

    $response = $this->actingAs($user)->put(route('customers.update', $customer), [
        'name' => 'Updated Name',
        'email' => $customer->email,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Original Name',
    ]);
});

test('user cannot delete a customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

    $response->assertForbidden();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
    ]);
});

test('user cannot update customer notes', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'notes' => 'Original notes',
    ]);

    $response = $this->actingAs($user)->patch(route('customers.update-notes', $customer), [
        'notes' => 'Updated notes',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'notes' => 'Original notes',
    ]);
});

test('admin cannot create, update, or delete customers in Stage 1', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $account = Account::factory()->create();
    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'name' => 'Test Customer',
    ]);

    // Test create
    $createResponse = $this->actingAs($admin)->post(route('customers.store'), [
        'name' => 'New Customer',
    ]);
    $createResponse->assertForbidden();

    // Test update
    $updateResponse = $this->actingAs($admin)->put(route('customers.update', $customer), [
        'name' => 'Updated Customer',
        'email' => $customer->email,
    ]);
    $updateResponse->assertForbidden();

    // Test delete
    $deleteResponse = $this->actingAs($admin)->delete(route('customers.destroy', $customer));
    $deleteResponse->assertForbidden();

    // Verify nothing changed
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Test Customer',
    ]);
});
