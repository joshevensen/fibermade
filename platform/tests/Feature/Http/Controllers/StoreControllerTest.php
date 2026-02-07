<?php

use App\Enums\InviteType;
use App\Models\Creator;
use App\Models\Invite;
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
