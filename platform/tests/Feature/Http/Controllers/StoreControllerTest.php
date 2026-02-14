<?php

use App\Enums\InviteType;
use App\Enums\OrderType;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;

test('creator index merges stores and pending invites when status is all', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'active']);

    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'invited@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Invited Store', 'owner_name' => 'Jane'],
    ]);

    $response = $this->actingAs($user)->get(route('stores.index', ['status' => 'all']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/stores/StoreIndexPage')
        ->has('stores', 2)
        ->where('totalStores', 1)
        ->where('filteredCount', 2)
        ->has('stores.0', fn ($item) => $item
            ->where('item_type', 'invite')
            ->where('is_invited', true)
            ->where('invite_id', $invite->id)
            ->where('status', 'invited')
            ->where('name', 'Invited Store')
            ->where('email', 'invited@example.com')
            ->etc()
        )
        ->has('stores.1', fn ($item) => $item
            ->where('item_type', 'store')
            ->where('is_invited', false)
            ->where('id', $store->id)
            ->where('status', 'active')
            ->etc()
        )
    );
});

test('creator index filter invited returns only invites', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'active']);

    Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'invited@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => ['store_name' => 'Invited Store'],
    ]);

    $response = $this->actingAs($user)->get(route('stores.index', ['status' => 'invited']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('stores', 1)
        ->where('totalStores', 1)
        ->where('filteredCount', 1)
        ->where('stores.0.item_type', 'invite')
        ->where('stores.0.is_invited', true)
    );
});

test('creator index filter active returns only stores with pivot status active', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $store = Store::factory()->create(['name' => 'Active Store']);
    $creator->stores()->attach($store->id, ['status' => 'active']);

    Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'invited@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [],
    ]);

    $response = $this->actingAs($user)->get(route('stores.index', ['status' => 'active']));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('stores', 1)
        ->where('totalStores', 1)
        ->where('filteredCount', 1)
        ->where('stores.0.item_type', 'store')
        ->where('stores.0.id', $store->id)
        ->where('stores.0.is_invited', false)
        ->where('stores.0.status', 'active')
    );
});

test('creator index stores have list_key and discriminator', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);

    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'active']);

    $invite = Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'i@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
        'metadata' => [],
    ]);

    $response = $this->actingAs($user)->get(route('stores.index', ['status' => 'all']));

    $response->assertSuccessful();
    $stores = $response->inertiaProps('stores');
    expect($stores)->toHaveCount(2);
    expect($stores[0])->toHaveKey('list_key', 'invite-'.$invite->id);
    expect($stores[1])->toHaveKey('list_key', 'store-'.$store->id);
});

test('creator edit loads pivot data and orders', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create(['name' => 'Test Store']);
    $creator->stores()->attach($store->id, [
        'status' => 'paused',
        'discount_rate' => 15.5,
        'payment_terms' => 'Net 30',
    ]);

    $response = $this->actingAs($user)->get(route('stores.edit', ['store' => $store->id]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/stores/StoreEditPage')
        ->where('store.id', $store->id)
        ->where('store.name', 'Test Store')
        ->where('store.status', 'paused')
        ->where('store.discount_rate', 15.5)
        ->where('store.payment_terms', 'Net 30')
        ->has('orders')
        ->has('ordersTruncated')
    );
});

test('creator edit returns 404 when creator has no relationship with store', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create();
    // Do not attach store to creator

    $response = $this->actingAs($user)->get(route('stores.edit', ['store' => $store->id]));

    $response->assertNotFound();
});

test('creator update saves to pivot only and does not change store model or status', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create(['name' => 'Original Name', 'email' => 'store@example.com']);
    $creator->stores()->attach($store->id, [
        'status' => 'active',
        'discount_rate' => 10,
    ]);

    $response = $this->actingAs($user)->patch(route('stores.update', ['store' => $store->id]), [
        'discount_rate' => 20,
        'payment_terms' => 'Net 60',
        'minimum_order_quantity' => 12,
        'minimum_order_value' => 100,
        'lead_time_days' => 14,
        'allows_preorders' => true,
        'notes' => 'Wholesale notes',
    ]);

    $response->assertRedirect(route('stores.index'));

    $store->refresh();
    expect($store->name)->toBe('Original Name');
    expect($store->email)->toBe('store@example.com');

    $pivot = $creator->stores()->where('stores.id', $store->id)->first()->pivot;
    expect($pivot->status)->toBe('active');
    expect((float) $pivot->discount_rate)->toBe(20.0);
    expect($pivot->payment_terms)->toBe('Net 60');
    expect((int) $pivot->minimum_order_quantity)->toBe(12);
    expect((float) $pivot->minimum_order_value)->toBe(100.0);
    expect((int) $pivot->lead_time_days)->toBe(14);
    expect((bool) $pivot->allows_preorders)->toBeTrue();
    expect($pivot->notes)->toBe('Wholesale notes');
});

test('creator edit order history is scoped to creator account', function () {
    $creatorA = Creator::factory()->create();
    $userA = User::factory()->create(['account_id' => $creatorA->account_id]);
    $creatorB = Creator::factory()->create();
    $store = Store::factory()->create();
    $creatorA->stores()->attach($store->id, ['status' => 'active']);
    $creatorB->stores()->attach($store->id, ['status' => 'active']);

    $orderA = Order::factory()->create([
        'account_id' => $creatorA->account_id,
        'type' => OrderType::Wholesale,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);
    $orderB = Order::factory()->create([
        'account_id' => $creatorB->account_id,
        'type' => OrderType::Wholesale,
        'orderable_type' => Store::class,
        'orderable_id' => $store->id,
    ]);

    $response = $this->actingAs($userA)->get(route('stores.edit', ['store' => $store->id]));

    $response->assertSuccessful();
    $orders = $response->inertiaProps('orders');
    expect($orders)->toHaveCount(1);
    expect($orders[0]['id'])->toBe($orderA->id);
});

test('creator can update store relationship status to paused', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'active']);

    $response = $this->actingAs($user)->patch(route('stores.status', ['store' => $store->id]), [
        'status' => 'paused',
    ]);

    $response->assertRedirect(route('stores.edit', ['store' => $store->id]));
    $pivot = $creator->stores()->where('stores.id', $store->id)->first()->pivot;
    expect($pivot->status)->toBe('paused');
});

test('creator can update store relationship status from paused to active', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'paused']);

    $response = $this->actingAs($user)->patch(route('stores.status', ['store' => $store->id]), [
        'status' => 'active',
    ]);

    $response->assertRedirect(route('stores.edit', ['store' => $store->id]));
    $pivot = $creator->stores()->where('stores.id', $store->id)->first()->pivot;
    expect($pivot->status)->toBe('active');
});

test('creator cannot change status from ended', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'ended']);

    $response = $this->actingAs($user)->patch(route('stores.status', ['store' => $store->id]), [
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('status');
    $pivot = $creator->stores()->where('stores.id', $store->id)->first()->pivot;
    expect($pivot->status)->toBe('ended');
});

test('non creator cannot update store relationship status', function () {
    $creator = Creator::factory()->create();
    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, ['status' => 'active']);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser)->patch(route('stores.status', ['store' => $store->id]), [
        'status' => 'paused',
    ]);

    $response->assertForbidden();
    $pivot = $creator->stores()->where('stores.id', $store->id)->first()->pivot;
    expect($pivot->status)->toBe('active');
});

test('creator index includes discount_rate and payment_terms in store items', function () {
    $creator = Creator::factory()->create();
    $user = User::factory()->create(['account_id' => $creator->account_id]);
    $store = Store::factory()->create();
    $creator->stores()->attach($store->id, [
        'status' => 'active',
        'discount_rate' => 25,
        'payment_terms' => 'Net 45',
    ]);

    $response = $this->actingAs($user)->get(route('stores.index', ['status' => 'active']));

    $response->assertSuccessful();
    $response->assertSuccessful();
    $stores = $response->inertiaProps('stores');
    expect($stores)->toHaveCount(1);
    expect($stores[0]['id'])->toBe($store->id);
    expect($stores[0]['payment_terms'])->toBe('Net 45');
    expect($stores[0]['discount_rate'])->toBeIn([25, 25.0]);
});
