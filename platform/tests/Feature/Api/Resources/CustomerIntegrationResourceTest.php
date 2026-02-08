<?php

use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\ExternalIdentifierResource;
use App\Http\Resources\Api\V1\IntegrationLogResource;
use App\Http\Resources\Api\V1\IntegrationResource;
use App\Models\Account;
use App\Models\Customer;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Order;

test('CustomerResource serializes all fields', function () {
    $account = Account::factory()->create();
    $customer = Customer::factory()->create([
        'account_id' => $account->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '555-1234',
        'address_line1' => '123 Main St',
        'address_line2' => 'Apt 4',
        'city' => 'Portland',
        'state_region' => 'OR',
        'postal_code' => '97201',
        'country_code' => 'US',
        'notes' => 'VIP customer',
    ]);
    $customer->unsetRelations();

    $resource = CustomerResource::make($customer);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'name', 'email', 'phone', 'address_line1', 'address_line2',
        'city', 'state_region', 'postal_code', 'country_code', 'notes',
        'created_at', 'updated_at',
    ])
        ->and($data['name'])->toBe('Jane Doe')
        ->and($data['email'])->toBe('jane@example.com');
});

test('IntegrationResource serializes core fields and excludes credentials', function () {
    $account = Account::factory()->create();
    $integration = Integration::create([
        'account_id' => $account->id,
        'type' => \App\Enums\IntegrationType::Shopify,
        'credentials' => 'encrypted-secret-data',
        'settings' => ['store_url' => 'https://example.myshopify.com'],
        'active' => true,
    ]);
    $integration->unsetRelations();

    $resource = IntegrationResource::make($integration);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'type', 'settings', 'active', 'created_at', 'updated_at',
    ])
        ->and($data)->not->toHaveKey('credentials')
        ->and($data['type'])->toBe('shopify')
        ->and($data['settings'])->toEqual(['store_url' => 'https://example.myshopify.com']);
});

test('IntegrationResource includes logs when loaded', function () {
    $account = Account::factory()->create();
    $integration = Integration::create([
        'account_id' => $account->id,
        'type' => \App\Enums\IntegrationType::Shopify,
        'credentials' => 'encrypted',
        'settings' => null,
        'active' => true,
    ]);
    IntegrationLog::create([
        'integration_id' => $integration->id,
        'loggable_type' => Order::class,
        'loggable_id' => 999,
        'status' => \App\Enums\IntegrationLogStatus::Success,
        'message' => 'Synced successfully',
    ]);

    $integration->load('logs');

    $resource = IntegrationResource::make($integration);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKey('logs')
        ->and($data['logs']['data'] ?? $data['logs'])->toBeArray()
        ->and(count($data['logs']['data'] ?? $data['logs']))->toBe(1);
});

test('IntegrationLogResource serializes all fields including polymorphic type and id', function () {
    $account = Account::factory()->create();
    $integration = Integration::create([
        'account_id' => $account->id,
        'type' => \App\Enums\IntegrationType::Shopify,
        'credentials' => 'encrypted',
        'settings' => null,
        'active' => true,
    ]);
    $log = IntegrationLog::create([
        'integration_id' => $integration->id,
        'loggable_type' => Order::class,
        'loggable_id' => 42,
        'status' => \App\Enums\IntegrationLogStatus::Error,
        'message' => 'Sync failed',
        'metadata' => ['error_code' => 500],
        'synced_at' => now(),
    ]);
    $log->unsetRelations();

    $resource = IntegrationLogResource::make($log);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'integration_id', 'loggable_type', 'loggable_id', 'status',
        'message', 'metadata', 'synced_at', 'created_at', 'updated_at',
    ])
        ->and($data['loggable_type'])->toBe(Order::class)
        ->and($data['loggable_id'])->toBe(42)
        ->and($data['status'])->toBe('error')
        ->and($data['metadata'])->toEqual(['error_code' => 500]);
});

test('ExternalIdentifierResource serializes all fields including polymorphic type and id', function () {
    $account = Account::factory()->create();
    $integration = Integration::create([
        'account_id' => $account->id,
        'type' => \App\Enums\IntegrationType::Shopify,
        'credentials' => 'encrypted',
        'settings' => null,
        'active' => true,
    ]);
    $customer = Customer::factory()->create(['account_id' => $account->id]);
    $identifier = ExternalIdentifier::create([
        'integration_id' => $integration->id,
        'identifiable_type' => Customer::class,
        'identifiable_id' => $customer->id,
        'external_type' => 'customer',
        'external_id' => 'gid://shopify/Customer/12345',
        'data' => ['admin_url' => 'https://admin.shopify.com/...'],
    ]);
    $identifier->unsetRelations();

    $resource = ExternalIdentifierResource::make($identifier);
    $array = $resource->response()->getData(true);

    $data = (isset($array['data']['id'])) ? $array['data'] : $array;

    expect($data)->toHaveKeys([
        'id', 'integration_id', 'identifiable_type', 'identifiable_id',
        'external_type', 'external_id', 'data', 'created_at', 'updated_at',
    ])
        ->and($data['identifiable_type'])->toBe(Customer::class)
        ->and($data['identifiable_id'])->toBe($customer->id)
        ->and($data['external_type'])->toBe('customer')
        ->and($data['external_id'])->toBe('gid://shopify/Customer/12345')
        ->and($data['data'])->toEqual(['admin_url' => 'https://admin.shopify.com/...']);
});
