<?php

use App\Enums\IntegrationLogStatus;
use App\Models\Account;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\User;

test('unauthenticated request returns 401', function () {
    $account = Account::factory()->create();
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $this->getJson("/api/v1/integrations/{$integration->id}/logs")
        ->assertStatus(401);
});

test('returns logs for integration newest first', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    $first = IntegrationLog::create([
        'integration_id' => $integration->id,
        'loggable_type' => \App\Models\Order::class,
        'loggable_id' => 1,
        'status' => IntegrationLogStatus::Success,
        'message' => 'First',
    ]);
    $second = IntegrationLog::create([
        'integration_id' => $integration->id,
        'loggable_type' => \App\Models\Order::class,
        'loggable_id' => 2,
        'status' => IntegrationLogStatus::Success,
        'message' => 'Second',
    ]);
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/integrations/{$integration->id}/logs", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and(count($data))->toBe(2);
    expect($data[0]['id'])->toBe($second->id);
    expect($data[1]['id'])->toBe($first->id);
    expect($data[0])->toHaveKeys(['id', 'integration_id', 'status', 'message', 'created_at']);
});

test('limit parameter restricts number of logs returned', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    for ($i = 0; $i < 15; $i++) {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => \App\Models\Order::class,
            'loggable_id' => $i,
            'status' => IntegrationLogStatus::Success,
            'message' => "Log {$i}",
        ]);
    }
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/integrations/{$integration->id}/logs?limit=10", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and(count($data))->toBe(10);
});

test('default limit is 50', function () {
    $account = Account::factory()->create();
    $user = User::factory()->create(['account_id' => $account->id]);
    $integration = Integration::factory()->create(['account_id' => $account->id]);
    for ($i = 0; $i < 55; $i++) {
        IntegrationLog::create([
            'integration_id' => $integration->id,
            'loggable_type' => \App\Models\Order::class,
            'loggable_id' => $i,
            'status' => IntegrationLogStatus::Success,
            'message' => "Log {$i}",
        ]);
    }
    $token = getApiToken($user);

    $response = $this->getJson("/api/v1/integrations/{$integration->id}/logs", withBearer($token));

    $response->assertStatus(200);
    $data = $response->json('data');
    expect($data)->toBeArray()
        ->and(count($data))->toBe(50);
});

test('accessing logs for another account integration returns 403', function () {
    $accountA = Account::factory()->create();
    $accountB = Account::factory()->create();
    $userB = User::factory()->create(['account_id' => $accountB->id]);
    $integrationA = Integration::factory()->create(['account_id' => $accountA->id]);
    $token = getApiToken($userB);

    $response = $this->getJson("/api/v1/integrations/{$integrationA->id}/logs", withBearer($token));

    $response->assertForbidden();
});

test('logs for non-existent integration returns 404', function () {
    $user = User::factory()->create(['account_id' => Account::factory()->create()->id]);
    $token = getApiToken($user);

    $response = $this->getJson('/api/v1/integrations/999999/logs', withBearer($token));

    $response->assertNotFound();
});
