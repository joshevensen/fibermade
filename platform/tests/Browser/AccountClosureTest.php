<?php

use App\Enums\BaseStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Creator;
use App\Models\User;

test('user can delete their account from settings page', function () {
    $account = Account::factory()->create([
        'status' => BaseStatus::Active,
    ]);
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create([
        'account_id' => $account->id,
        'role' => UserRole::Owner,
    ]);

    $accountId = $account->id;

    $page = visit('/login');

    $page->fill('input[name="email"]', $user->email)
        ->fill('input[name="password"]', 'password')
        ->click('button[type="submit"]')
        ->wait(1)
        ->assertPathIs('/creator/dashboard')
        ->navigate('/creator/settings?tab=account')
        ->assertSee('Settings')
        ->assertNoJavascriptErrors()
        // Click delete account button
        ->click('[data-test="delete-account-button"]')
        ->wait(1)
        ->assertSee('Are you sure')
        // Confirm deletion with password
        ->fill('input[placeholder="Password"]', 'password')
        ->click('[data-test="confirm-delete-account-button"]')
        ->wait(2)
        ->assertPathIs('/')
        ->assertNoJavascriptErrors();

    // Verify account was soft deleted
    expect(Account::find($accountId))->toBeNull();
    expect(Account::withTrashed()->find($accountId))->not->toBeNull();
    expect(Account::withTrashed()->find($accountId)->deleted_at)->not->toBeNull();
});

test('account deletion requires correct password', function () {
    $account = Account::factory()->create([
        'status' => BaseStatus::Active,
    ]);
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create([
        'account_id' => $account->id,
        'role' => UserRole::Owner,
    ]);

    $page = visit('/login');

    $page->fill('input[name="email"]', $user->email)
        ->fill('input[name="password"]', 'password')
        ->click('button[type="submit"]')
        ->wait(1)
        ->navigate('/creator/settings?tab=account')
        ->click('[data-test="delete-account-button"]')
        ->wait(1)
        ->fill('input[placeholder="Password"]', 'wrong-password')
        ->click('[data-test="confirm-delete-account-button"]')
        ->wait(1)
        ->assertNoJavascriptErrors();

    // Verify account was NOT deleted
    expect($account->fresh())->not->toBeNull();
    expect($account->fresh()->deleted_at)->toBeNull();
});

test('account deletion dialog can be cancelled', function () {
    $account = Account::factory()->create([
        'status' => BaseStatus::Active,
    ]);
    $creator = Creator::factory()->create(['account_id' => $account->id]);
    $user = User::factory()->create([
        'account_id' => $account->id,
        'role' => UserRole::Owner,
    ]);

    $page = visit('/login');

    $page->fill('input[name="email"]', $user->email)
        ->fill('input[name="password"]', 'password')
        ->click('button[type="submit"]')
        ->wait(1)
        ->navigate('/creator/settings?tab=account')
        ->click('[data-test="delete-account-button"]')
        ->wait(1)
        ->click('button:has-text("Cancel")')
        ->wait(0.5)
        ->assertNoJavascriptErrors();

    expect($account->fresh())->not->toBeNull();
});
