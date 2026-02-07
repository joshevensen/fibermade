<?php

use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\Colorway;
use App\Models\User;

test('user can create a colorway without slug', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $response = $this->actingAs($user)->post(route('colorways.store'), [
        'name' => 'Test Colorway',
        'status' => ColorwayStatus::Active->value,
        'technique' => Technique::Solid->value,
        'per_pan' => 3,
    ]);

    $response->assertRedirect(route('colorways.index'));
    $this->assertDatabaseHas('colorways', [
        'account_id' => $account->id,
        'name' => 'Test Colorway',
        'status' => ColorwayStatus::Active->value,
    ]);
    $this->assertDatabaseMissing('colorways', [
        'account_id' => $account->id,
        'name' => 'Test Colorway',
        'slug' => 'test-colorway',
    ]);
});

test('user can update a colorway without slug', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);

    $colorway = Colorway::factory()->create([
        'account_id' => $account->id,
        'name' => 'Original Colorway',
    ]);

    $response = $this->actingAs($user)->put(route('colorways.update', $colorway), [
        'name' => 'Updated Colorway',
        'status' => ColorwayStatus::Active->value,
    ]);

    $response->assertRedirect(route('colorways.index'));
    $this->assertDatabaseHas('colorways', [
        'id' => $colorway->id,
        'name' => 'Updated Colorway',
    ]);
});
