<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

function stripeWebhookSignature(string $payload, string $secret): string
{
    $timestamp = time();

    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return 't='.$timestamp.',v1='.$signature;
}

beforeEach(function () {
    Config::set('services.stripe.webhook_secret', 'whsec_test_secret');
    Config::set('services.stripe.secret', 'sk_test_placeholder');
});

test('checkout.session.completed creates user from cache when registration_token is valid', function () {
    $registrationToken = 'test-registration-token-'.bin2hex(random_bytes(8));
    $plainPassword = 'SecurePassword123!';
    $email = 'completed-checkout@example.com';

    Cache::put('registration_pending:'.$registrationToken, [
        'email' => $email,
        'name' => 'Completed User',
        'business_name' => 'Completed Business',
        'password_hash' => Hash::make($plainPassword),
        'promo_code' => null,
    ], now()->addMinutes(30));

    $payload = json_encode([
        'id' => 'evt_test_'.uniqid(),
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'customer' => 'cus_test_123',
                'metadata' => [
                    'email' => $email,
                    'name' => 'Completed User',
                    'business_name' => 'Completed Business',
                    'registration_token' => $registrationToken,
                ],
            ],
        ],
    ]);

    $signature = stripeWebhookSignature($payload, 'whsec_test_secret');

    $response = $this->call(
        'POST',
        route('webhooks.stripe'),
        [],
        [],
        [],
        ['HTTP_STRIPE_SIGNATURE' => $signature],
        $payload
    );

    $response->assertStatus(200);

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Completed User');
    expect(Hash::check($plainPassword, $user->password))->toBeTrue();

    expect(Cache::has('registration_pending:'.$registrationToken))->toBeFalse();
});

test('checkout.session.completed does not create user when registration_token has no cache entry', function () {
    $email = 'no-cache@example.com';

    $payload = json_encode([
        'id' => 'evt_test_'.uniqid(),
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'customer' => 'cus_test_456',
                'metadata' => [
                    'email' => $email,
                    'name' => 'No Cache User',
                    'business_name' => 'No Cache Business',
                    'registration_token' => 'nonexistent-token',
                ],
            ],
        ],
    ]);

    $signature = stripeWebhookSignature($payload, 'whsec_test_secret');

    $response = $this->call(
        'POST',
        route('webhooks.stripe'),
        [],
        [],
        [],
        ['HTTP_STRIPE_SIGNATURE' => $signature],
        $payload
    );

    $response->assertStatus(200);
    expect(User::where('email', $email)->exists())->toBeFalse();
});
