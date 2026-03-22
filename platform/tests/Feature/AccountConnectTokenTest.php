<?php

use App\Models\Account;
use Illuminate\Support\Str;

it('auto-generates a connect token when creating an account', function () {
    $account = Account::factory()->create();

    expect($account->shopify_connect_token)
        ->not->toBeNull()
        ->toBeString()
        ->toHaveLength(36); // UUID v4 format
});

it('generates a unique connect token per account', function () {
    $first = Account::factory()->create();
    $second = Account::factory()->create();

    expect($first->shopify_connect_token)->not->toBe($second->shopify_connect_token);
});

it('does not overwrite an existing connect token on creation', function () {
    $token = (string) Str::uuid();
    $account = Account::factory()->create(['shopify_connect_token' => $token]);

    expect($account->shopify_connect_token)->toBe($token);
});

it('replaces the connect token via generateConnectToken()', function () {
    $account = Account::factory()->create();
    $original = $account->shopify_connect_token;

    $account->generateConnectToken();

    expect($account->fresh()->shopify_connect_token)
        ->not->toBe($original)
        ->not->toBeNull();
});

it('generates a valid UUID from generateConnectToken()', function () {
    $account = Account::factory()->create();

    $account->generateConnectToken();

    expect($account->fresh()->shopify_connect_token)->toHaveLength(36);
});
