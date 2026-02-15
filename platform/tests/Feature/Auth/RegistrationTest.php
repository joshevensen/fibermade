<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Creator;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register with all required fields', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'business_name' => 'Test Business',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => true,
        'privacy_accepted' => true,
        'marketing_opt_in' => false,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirectToRoute('dashboard');

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->account_id)->not->toBeNull();
    expect($user->terms_accepted_at)->not->toBeNull();
    expect($user->privacy_accepted_at)->not->toBeNull();
    expect($user->marketing_opt_in)->toBeFalse();

    $account = Account::find($user->account_id);
    expect($account)->not->toBeNull();
    expect($account->type)->toBe(AccountType::Creator);

    $creator = Creator::where('account_id', $account->id)->first();
    expect($creator)->not->toBeNull();
    expect($creator->name)->toBe('Test Business');
    expect($creator->email)->toBe('test@example.com');
});

test('registration requires business name', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ]);

    $response->assertSessionHasErrors('business_name');
});

test('registration requires terms acceptance', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'business_name' => 'Test Business',
        'password' => 'password',
        'password_confirmation' => 'password',
        'privacy_accepted' => true,
    ]);

    $response->assertSessionHasErrors('terms_accepted');
});

test('registration requires privacy acceptance', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'business_name' => 'Test Business',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => true,
    ]);

    $response->assertSessionHasErrors('privacy_accepted');
});

test('registration allows any email', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'anyone@example.com',
        'business_name' => 'Test Business',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirectToRoute('dashboard');
});
