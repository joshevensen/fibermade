<?php

use App\Enums\BaseStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;

test('user page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('user.edit'));

    $response->assertOk();
});

test('user information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('user.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('user.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('user.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('user.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $account = Account::factory()->create([
        'status' => BaseStatus::Active,
    ]);

    $user = User::factory()->create([
        'account_id' => $account->id,
        'role' => UserRole::Owner,
    ]);

    $accountId = $account->id;
    $userId = $user->id;

    $response = $this
        ->actingAs($user)
        ->delete(route('account.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect(Account::withTrashed()->find($accountId))->not->toBeNull();
    expect(Account::withTrashed()->find($accountId)->deleted_at)->not->toBeNull();
    expect(Account::find($accountId))->toBeNull();
    expect(User::find($userId))->not->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $account = Account::factory()->create([
        'status' => BaseStatus::Active,
    ]);

    $user = User::factory()->create([
        'account_id' => $account->id,
        'role' => UserRole::Owner,
    ]);

    $response = $this
        ->actingAs($user)
        ->from(route('user.edit'))
        ->delete(route('account.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('user.edit'));

    expect($account->fresh())->not->toBeNull();
    expect($user->fresh())->not->toBeNull();
});
