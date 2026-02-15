<?php

use App\Enums\InviteType;
use App\Enums\OrderStatus;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Invite;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('creator dashboard returns correct colorway count', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    Colorway::factory()->count(3)->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->where('colorwayCount', 3)
    );
});

test('creator dashboard returns correct collection count', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    Collection::factory()->count(2)->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->where('collectionCount', 2)
    );
});

test('creator dashboard returns correct store count', function () {
    $account = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    $store1 = Store::factory()->create();
    $store2 = Store::factory()->create();
    $creator->stores()->attach($store1->id, ['status' => 'active']);
    $creator->stores()->attach($store2->id, ['status' => 'active']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->where('storeCount', 2)
    );
});

test('creator dashboard returns active orders grouped by status', function () {
    $account = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create();
    $store->creators()->attach($creator->id, ['status' => 'active']);

    $openOrder = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);
    $acceptedOrder = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Accepted,
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->has('activeOrders.open', 1)
        ->has('activeOrders.accepted', 1)
        ->where('activeOrders.open.0.id', $openOrder->id)
        ->where('activeOrders.accepted.0.id', $acceptedOrder->id)
    );
});

test('creator dashboard excludes cancelled orders', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create();

    Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Cancelled,
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->missing('activeOrders.cancelled')
    );
});

test('creator dashboard includes only delivered orders from last 30 days', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create();

    $recentDelivered = Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Delivered,
        'delivered_at' => now()->subDays(5),
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);
    Order::factory()->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Delivered,
        'delivered_at' => now()->subDays(45),
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->has('activeOrders.delivered', 1)
        ->where('activeOrders.delivered.0.id', $recentDelivered->id)
    );
});

test('creator dashboard returns pending orders count in needsAttention', function () {
    $account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create();

    Order::factory()->count(2)->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->where('needsAttention.pending_orders', 2)
    );
});

test('creator dashboard returns pending store invites count in needsAttention', function () {
    $account = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);

    Invite::create([
        'invite_type' => InviteType::Store,
        'email' => 'store@example.com',
        'token' => str()->random(64),
        'expires_at' => now()->addDays(7),
        'inviter_type' => Creator::class,
        'inviter_id' => $creator->id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('creator/dashboard/DashboardPage')
        ->where('needsAttention.pending_store_invites', 1)
    );
});

test('creator dashboard eager loads order relationships', function () {
    $account = Account::factory()->creator()->create();
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create(['account_id' => $account->id]);
    $store = Store::factory()->create();
    $store->creators()->attach($creator->id, ['status' => 'active']);

    Order::factory()->count(3)->create([
        'account_id' => $account->id,
        'status' => OrderStatus::Open,
        'orderable_id' => $store->id,
        'orderable_type' => Store::class,
    ]);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $this->actingAs($user)->get(route('dashboard'));

    expect($queryCount)->toBeLessThan(20);
});
