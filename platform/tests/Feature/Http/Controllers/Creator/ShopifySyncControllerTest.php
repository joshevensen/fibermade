<?php

use App\Enums\IntegrationType;
use App\Exceptions\SyncAlreadyRunningException;
use App\Jobs\PushCatalogJob;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Integration;
use App\Models\User;
use App\Services\Shopify\ShopifySyncOrchestrator;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    Creator::factory()->create(['account_id' => $this->account->id]);
    $this->user = User::factory()->create(['account_id' => $this->account->id]);

    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'shpat_test']),
        'settings' => [
            'shop' => 'test.myshopify.com',
            'auto_sync' => false,
            'sync' => ['status' => 'idle'],
        ],
    ]);
});

// ─── Auth guard ───────────────────────────────────────────────────────────────

it('redirects guests away from sync endpoints', function () {
    $this->postJson(route('shopify.pull.all'))->assertRedirect(route('login'));
    $this->getJson(route('shopify.pull.status'))->assertRedirect(route('login'));
});

// ─── 404 when no integration ─────────────────────────────────────────────────

it('returns 404 when no active Shopify integration exists for syncAll', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.all'))
        ->assertNotFound();
});

it('returns 404 when no active Shopify integration exists for syncProducts', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.colorways'))
        ->assertNotFound();
});

it('returns 404 when no active Shopify integration exists for syncCollections', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.collections'))
        ->assertNotFound();
});

it('returns 404 when no active Shopify integration exists for syncInventory', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.inventory'))
        ->assertNotFound();
});

it('returns 404 for status when no active Shopify integration exists', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->getJson(route('shopify.pull.status'))
        ->assertNotFound()
        ->assertJson(['connected' => false]);
});

// ─── 409 when already running ─────────────────────────────────────────────────

it('returns 409 when sync is already running for syncAll', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullAll')->andThrow(new SyncAlreadyRunningException('Already running'));
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.all'))
        ->assertStatus(409)
        ->assertJson(['message' => 'A sync is already running.']);
});

it('returns 409 when sync is already running for syncProducts', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullColorways')->andThrow(new SyncAlreadyRunningException('Already running'));
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.colorways'))
        ->assertStatus(409);
});

it('returns 409 when sync is already running for syncCollections', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullCollections')->andThrow(new SyncAlreadyRunningException('Already running'));
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.collections'))
        ->assertStatus(409);
});

it('returns 409 when sync is already running for syncInventory', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullInventory')->andThrow(new SyncAlreadyRunningException('Already running'));
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.inventory'))
        ->assertStatus(409);
});

// ─── 202 when sync dispatched ─────────────────────────────────────────────────

it('returns 202 with sync state after triggering syncAll', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullAll')->once()->andReturnNull();
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.all'))
        ->assertStatus(202)
        ->assertJsonStructure(['message', 'sync']);
});

it('returns 202 with sync state after triggering syncProducts', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullColorways')->once()->andReturnNull();
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.colorways'))
        ->assertStatus(202)
        ->assertJsonStructure(['message', 'sync']);
});

it('returns 202 with sync state after triggering syncCollections', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullCollections')->once()->andReturnNull();
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.collections'))
        ->assertStatus(202)
        ->assertJsonStructure(['message', 'sync']);
});

it('returns 202 with sync state after triggering syncInventory', function () {
    $this->mock(ShopifySyncOrchestrator::class, function ($mock) {
        $mock->shouldReceive('pullInventory')->once()->andReturnNull();
    });

    $this->actingAs($this->user)
        ->postJson(route('shopify.pull.inventory'))
        ->assertStatus(202)
        ->assertJsonStructure(['message', 'sync']);
});

// ─── Status endpoint ─────────────────────────────────────────────────────────

it('returns correct shape from status endpoint', function () {
    $this->actingAs($this->user)
        ->getJson(route('shopify.pull.status'))
        ->assertOk()
        ->assertJson([
            'connected' => true,
            'shop' => 'test.myshopify.com',
            'auto_sync' => false,
            'sync' => ['status' => 'idle'],
            'push_sync' => ['status' => 'idle'],
        ]);
});

// ─── pushAll ──────────────────────────────────────────────────────────────────

it('returns 404 for pushAll when no active integration exists', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->postJson(route('shopify.push.all'))
        ->assertNotFound();
});

it('returns 409 for pushAll when a push is already running', function () {
    $settings = $this->integration->settings ?? [];
    $settings['push_sync'] = ['status' => 'running'];
    $this->integration->update(['settings' => $settings]);

    $this->actingAs($this->user)
        ->postJson(route('shopify.push.all'))
        ->assertStatus(409)
        ->assertJson(['message' => 'A push is already running.']);
});

it('dispatches PushCatalogJob and returns 202', function () {
    Queue::fake();

    $this->actingAs($this->user)
        ->postJson(route('shopify.push.all'))
        ->assertStatus(202)
        ->assertJsonStructure(['message', 'push_sync']);

    Queue::assertPushed(PushCatalogJob::class, fn ($job) => $job->integrationId === $this->integration->id);
});

it('redirects guests away from pushAll endpoint', function () {
    $this->postJson(route('shopify.push.all'))->assertRedirect(route('login'));
});

// ─── updateSettings ──────────────────────────────────────────────────────────

it('saves auto_sync true to integration settings', function () {
    $this->actingAs($this->user)
        ->patchJson(route('shopify.settings.update'), ['auto_sync' => true])
        ->assertOk()
        ->assertJson(['auto_sync' => true]);

    expect($this->integration->fresh()->settings['auto_sync'])->toBeTrue();
});

it('saves auto_sync false to integration settings', function () {
    $this->integration->update(['settings' => array_merge($this->integration->settings, ['auto_sync' => true])]);

    $this->actingAs($this->user)
        ->patchJson(route('shopify.settings.update'), ['auto_sync' => false])
        ->assertOk()
        ->assertJson(['auto_sync' => false]);

    expect($this->integration->fresh()->settings['auto_sync'])->toBeFalse();
});

it('returns 404 for updateSettings when no active integration exists', function () {
    $this->integration->update(['active' => false]);

    $this->actingAs($this->user)
        ->patchJson(route('shopify.settings.update'), ['auto_sync' => true])
        ->assertNotFound();
});

it('validates auto_sync is required in updateSettings', function () {
    $this->actingAs($this->user)
        ->patchJson(route('shopify.settings.update'), [])
        ->assertUnprocessable();
});
