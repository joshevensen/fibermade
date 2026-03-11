<?php

use App\Models\Account;
use App\Models\Colorway;
use App\Models\Media;
use App\Models\User;

test('unauthenticated request returns 401', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    $this->postJson('/api/v1/media', [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'https://cdn.shopify.com/image.jpg',
        'file_name' => 'image.jpg',
        'is_primary' => true,
        'metadata' => ['source' => 'shopify'],
    ])
        ->assertStatus(401);
});

test('creates media for colorway in same account and returns 201', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $payload = [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'https://cdn.shopify.com/s/files/1/0123/4567/files/image.jpg',
        'file_name' => 'image.jpg',
        'is_primary' => true,
        'metadata' => [
            'source' => 'shopify',
            'original_url' => 'https://cdn.shopify.com/s/files/1/0123/4567/files/image.jpg',
        ],
    ];

    $response = $this->postJson('/api/v1/media', $payload, withBearer($token));

    $response->assertStatus(201);
    $response->assertJsonPath('data.mediable_type', Colorway::class);
    $response->assertJsonPath('data.mediable_id', $colorway->id);
    $response->assertJsonPath('data.file_path', $payload['file_path']);
    $response->assertJsonPath('data.file_name', 'image.jpg');
    $response->assertJsonPath('data.is_primary', true);
    $response->assertJsonPath('data.metadata.source', 'shopify');

    $this->assertDatabaseHas(Media::class, [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => $payload['file_path'],
        'is_primary' => true,
    ]);
});

test('creates media with valid mime type', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/media', [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'https://cdn.shopify.com/image.jpg',
        'file_name' => 'image.jpg',
        'mime_type' => 'image/jpeg',
        'is_primary' => false,
    ], withBearer($token));

    $response->assertStatus(201);
});

test('creating media with invalid mime type returns 422', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $token = getApiToken($user);

    $response = $this->postJson('/api/v1/media', [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'https://cdn.shopify.com/file.exe',
        'file_name' => 'file.exe',
        'mime_type' => 'application/octet-stream',
        'is_primary' => false,
    ], withBearer($token));

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['mime_type']);
});

test('creating media for colorway in another account returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $colorwayA = Colorway::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->postJson('/api/v1/media', [
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorwayA->id,
        'file_path' => 'https://cdn.shopify.com/image.jpg',
        'file_name' => 'image.jpg',
        'is_primary' => true,
    ], withBearer($token));

    $response->assertForbidden();
});
