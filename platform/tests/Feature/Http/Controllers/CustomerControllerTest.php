<?php

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;

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

test('user can create a customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('customers.store'), [
        'name' => 'New Customer',
        'email' => 'customer@example.com',
    ]);

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', [
        'account_id' => $account->id,
        'name' => 'New Customer',
        'email' => 'customer@example.com',
    ]);
});

test('user can update a customer', function () {
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

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Name',
    ]);
});

test('user can delete a customer', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->delete(route('customers.destroy', $customer));

    $response->assertRedirect(route('customers.index'));
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});

test('user can update customer notes', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'notes' => 'Original notes',
    ]);

    $response = $this->actingAs($user)->patch(route('customers.update-notes', $customer), [
        'notes' => 'Updated notes',
    ]);

    $response->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'notes' => 'Updated notes',
    ]);
});

test('admin can update and delete customers', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $account = Account::factory()->create();
    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'name' => 'Test Customer',
    ]);

    $updateResponse = $this->actingAs($admin)->put(route('customers.update', $customer), [
        'name' => 'Updated Customer',
        'email' => $customer->email,
    ]);
    $updateResponse->assertRedirect(route('customers.index'));
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Customer',
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('customers.destroy', $customer));
    $deleteResponse->assertRedirect(route('customers.index'));
    $this->assertSoftDeleted('customers', ['id' => $customer->id]);
});
