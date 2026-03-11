<?php

use App\Models\Account;
use App\Models\Colorway;
use App\Models\User;

beforeEach(function () {
    $this->account = Account::factory()->create();
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
});

test('store rejects invalid mediable_type', function () {
    $response = $this->actingAs($this->user)->post(route('media.store'), [
        'mediable_type' => \App\Models\User::class,
        'mediable_id' => 1,
        'file_path' => 'test/path.jpg',
        'file_name' => 'path.jpg',
        'is_primary' => true,
    ]);

    $response->assertSessionHasErrors('mediable_type');
});

test('store rejects arbitrary string as mediable_type', function () {
    $response = $this->actingAs($this->user)->post(route('media.store'), [
        'mediable_type' => 'Some\\Fake\\Model',
        'mediable_id' => 1,
        'file_path' => 'test/path.jpg',
        'file_name' => 'path.jpg',
        'is_primary' => true,
    ]);

    $response->assertSessionHasErrors('mediable_type');
});

test('store accepts valid mediable_type', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    $response = $this->actingAs($this->user)->post(route('media.store'), [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/test.jpg',
        'file_name' => 'test.jpg',
        'is_primary' => true,
    ]);

    $response->assertRedirect(route('media.index'));
    $this->assertDatabaseHas('media', [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/test.jpg',
    ]);
});
