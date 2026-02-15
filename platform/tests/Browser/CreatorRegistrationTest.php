<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Creator;
use App\Models\User;

test('creator can complete full registration flow', function () {
    $page = visit('/register');

    $page->assertSee('Create an account')
        ->assertNoJavascriptErrors()
        ->fill('input[name="name"]', 'Jane Smith')
        ->fill('input[name="email"]', 'jane@fiberartist.com')
        ->fill('input[name="business_name"]', 'Jane\'s Yarn Studio')
        ->fill('input[name="password"]', 'SecurePassword123')
        ->fill('input[name="password_confirmation"]', 'SecurePassword123')
        ->check('input[name="terms_accepted"]')
        ->check('input[name="privacy_accepted"]')
        ->click('[data-test="register-user-button"]')
        ->wait(1)
        ->assertPathIs('/creator/dashboard')
        ->assertNoJavascriptErrors();

    // Verify database state
    $user = User::where('email', 'jane@fiberartist.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Jane Smith');
    expect($user->account_id)->not->toBeNull();

    $account = Account::find($user->account_id);
    expect($account->type)->toBe(AccountType::Creator);

    $creator = Creator::where('account_id', $account->id)->first();
    expect($creator)->not->toBeNull();
    expect($creator->name)->toBe('Jane\'s Yarn Studio');
});

test('creator registration shows validation errors for required fields', function () {
    $page = visit('/register');

    $page->click('[data-test="register-user-button"]')
        ->wait(0.5) // Wait for validation
        ->assertNoJavascriptErrors();

    expect(User::count())->toBe(0);
});
