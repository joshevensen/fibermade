<?php

use App\Models\User;

test('register checkout with invalid data returns validation errors', function () {
    $response = $this->post(route('register.checkout'), [
        'name' => '',
        'email' => 'invalid',
        'business_name' => '',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
        'terms_accepted' => false,
        'privacy_accepted' => false,
    ]);

    $response->assertSessionHasErrors(['name', 'email', 'business_name', 'password', 'terms_accepted', 'privacy_accepted']);
});

test('register checkout with existing email returns validation error', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->post(route('register.checkout'), [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'business_name' => 'Test Business',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ]);

    $response->assertSessionHasErrors(['email']);
});
