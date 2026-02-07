<?php

use App\Enums\BaseStatus;
use App\Models\Account;
use App\Models\Collection;
use App\Models\User;

test('user can create a collection without slug', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $user->account_id = $account->id;
    $user->save();

    $response = $this->actingAs($user)->post(route('collections.store'), [
        'name' => 'Test Collection',
        'status' => BaseStatus::Active->value,
    ]);

    $response->assertRedirect(route('collections.index'));
    $this->assertDatabaseHas('collections', [
        'account_id' => $account->id,
        'name' => 'Test Collection',
        'status' => BaseStatus::Active->value,
    ]);
    $this->assertDatabaseMissing('collections', [
        'account_id' => $account->id,
        'name' => 'Test Collection',
        'slug' => 'test-collection',
    ]);
});

test('user can update a collection without slug', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $user->account_id = $account->id;
    $user->save();

    $collection = Collection::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Collection',
    ]);

    $response = $this->actingAs($user)->put(route('collections.update', $collection), [
        'name' => 'Updated Collection',
        'status' => BaseStatus::Active->value,
    ]);

    $response->assertRedirect(route('collections.index'));
    $this->assertDatabaseHas('collections', [
        'id' => $collection->id,
        'name' => 'Updated Collection',
    ]);
});
