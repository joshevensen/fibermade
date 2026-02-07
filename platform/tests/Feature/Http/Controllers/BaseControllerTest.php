<?php

use App\Enums\BaseStatus;
use App\Enums\Weight;
use App\Models\Account;
use App\Models\Base;
use App\Models\User;

test('user can create a base without slug', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $user->account_id = $account->id;
    $user->save();

    $response = $this->actingAs($user)->post(route('bases.store'), [
        'descriptor' => 'Test Base',
        'status' => BaseStatus::Active->value,
        'weight' => Weight::Worsted->value,
    ]);

    $response->assertRedirect(route('bases.index'));
    $this->assertDatabaseHas('bases', [
        'account_id' => $account->id,
        'descriptor' => 'Test Base',
        'status' => BaseStatus::Active->value,
    ]);
    $this->assertDatabaseMissing('bases', [
        'account_id' => $account->id,
        'descriptor' => 'Test Base',
        'slug' => 'test-base',
    ]);
});

test('user can update a base without slug', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $user->account_id = $account->id;
    $user->save();

    $base = Base::factory()->create([
        'account_id' => $account->id,
        'descriptor' => 'Original Base',
    ]);

    $response = $this->actingAs($user)->put(route('bases.update', $base), [
        'descriptor' => 'Updated Base',
        'status' => BaseStatus::Active->value,
    ]);

    $response->assertRedirect(route('bases.index'));
    $this->assertDatabaseHas('bases', [
        'id' => $base->id,
        'descriptor' => 'Updated Base',
    ]);
});
