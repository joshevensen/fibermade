<?php

use App\Models\Account;
use App\Models\User;

test('authenticated creator can generate a Shopify API token', function () {
    $account = Account::factory()->creator()->create();
    $user = User::factory()->create([
        'account_id' => $account->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->postJson(route('user.api-token.store'));

    $response
        ->assertCreated()
        ->assertJsonStructure(['token']);

    $token = $response->json('token');
    expect($token)->toBeString()->not->toBeEmpty();

    $apiResponse = $this->getJson('/api/v1/health', withBearer($token));
    $apiResponse->assertSuccessful();
});

test('guests cannot generate a Shopify API token', function () {
    $response = $this->postJson(route('user.api-token.store'));

    $response->assertRedirect(route('login'));
});
