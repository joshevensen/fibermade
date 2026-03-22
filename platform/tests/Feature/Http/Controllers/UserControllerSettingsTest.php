<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\User;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $this->account->id]);
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
});

// ─── Guests redirected ────────────────────────────────────────────────────────

it('redirects guests from settings page', function () {
    $this->get(route('user.edit'))->assertRedirect(route('login'));
});

// ─── Shopify prop — not connected ─────────────────────────────────────────────

it('passes shopify prop with connected false when no active integration exists', function () {
    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page
                ->component('creator/settings/SettingsPage')
                ->has('shopify')
                ->where('shopify.connected', false)
                ->where('shopify.shop', null)
                ->where('shopify.auto_sync', false)
                ->where('shopify.recent_errors', []),
        );
});

// ─── Shopify prop — connected ─────────────────────────────────────────────────

it('passes shopify prop with connected true when active integration exists', function () {
    Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => [
            'shop' => 'mystore.myshopify.com',
            'auto_sync' => true,
            'sync' => ['status' => 'idle'],
        ],
    ]);

    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page
                ->where('shopify.connected', true)
                ->where('shopify.shop', 'mystore.myshopify.com')
                ->where('shopify.auto_sync', true)
                ->has('shopify.connected_since')
                ->has('shopify.sync')
                ->has('shopify.recent_errors'),
        );
});

it('does not include inactive integrations in shopify prop', function () {
    Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => false,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => ['shop' => 'inactive.myshopify.com'],
    ]);

    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page->where('shopify.connected', false),
        );
});

// ─── Shopify prop — recent errors ─────────────────────────────────────────────

it('includes up to 20 recent error logs in shopify prop', function () {
    $integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    // Create 25 error logs — only 20 most recent should appear
    IntegrationLog::factory()->count(25)->create([
        'integration_id' => $integration->id,
        'status' => IntegrationLogStatus::Error,
        'message' => 'Something went wrong',
    ]);

    // Create a success log — should not appear
    IntegrationLog::factory()->create([
        'integration_id' => $integration->id,
        'status' => IntegrationLogStatus::Success,
        'message' => 'All good',
    ]);

    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page
                ->where('shopify.connected', true)
                ->has('shopify.recent_errors', 20),
        );
});

it('each recent error has id, message, and created_at fields', function () {
    $integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    IntegrationLog::factory()->create([
        'integration_id' => $integration->id,
        'status' => IntegrationLogStatus::Error,
        'message' => 'Product sync failed',
    ]);

    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page
                ->has('shopify.recent_errors', 1)
                ->has('shopify.recent_errors.0.id')
                ->has('shopify.recent_errors.0.message')
                ->has('shopify.recent_errors.0.created_at'),
        );
});

// ─── Shopify prop — connect_token ─────────────────────────────────────────────

it('includes connect_token in shopify prop when not connected', function () {
    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page
                ->where('shopify.connected', false)
                ->where('shopify.connect_token', $this->account->shopify_connect_token),
        );
});

it('includes connect_token in shopify prop when connected', function () {
    Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => ['shop' => 'mystore.myshopify.com'],
    ]);

    $this->actingAs($this->user)
        ->get(route('user.edit'))
        ->assertInertia(
            fn ($page) => $page
                ->where('shopify.connected', true)
                ->where('shopify.connect_token', $this->account->shopify_connect_token),
        );
});

// ─── Reset connect token ───────────────────────────────────────────────────────

it('redirects guests from reset connect token endpoint', function () {
    $this->post(route('shopify-connect-token.reset'))
        ->assertRedirect(route('login'));
});

it('resets the connect token and returns the new token', function () {
    $owner = User::factory()->create([
        'account_id' => $this->account->id,
        'role' => UserRole::Owner,
    ]);

    $originalToken = $this->account->shopify_connect_token;

    $response = $this->actingAs($owner)
        ->postJson(route('shopify-connect-token.reset'));

    $response->assertOk()
        ->assertJsonStructure(['connect_token']);

    $newToken = $response->json('connect_token');

    expect($newToken)->not->toBe($originalToken);
    expect($this->account->fresh()->shopify_connect_token)->toBe($newToken);
});

it('returns 403 when user has no account', function () {
    $userWithoutAccount = User::factory()->create(['account_id' => null]);

    $this->actingAs($userWithoutAccount)
        ->postJson(route('shopify-connect-token.reset'))
        ->assertForbidden();
});
