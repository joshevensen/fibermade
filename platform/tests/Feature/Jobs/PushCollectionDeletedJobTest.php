<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Jobs\PushCollectionDeletedJob;
use App\Models\Account;
use App\Models\Collection;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.shopify.catalog_sync_enabled', true);

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

test('PushCollectionDeletedJob deletes collection and removes ExternalIdentifier', function () {
    $collectionId = 9999;

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collectionId,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/888',
    ]);

    Http::fake([
        'test.myshopify.com/admin/api/2025-01/custom_collections/888.json' => Http::response(null, 200),
    ]);

    $job = new PushCollectionDeletedJob($collectionId, $this->account->id);
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->url(), 'custom_collections/888.json') && $r->method() === 'DELETE');

    expect(ExternalIdentifier::where('identifiable_id', $collectionId)
        ->where('external_type', 'shopify_collection')
        ->exists()
    )->toBeFalse();
});

test('PushCollectionDeletedJob logs success after deletion', function () {
    $collectionId = 8888;

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Collection::class,
        'identifiable_id' => $collectionId,
        'external_type' => 'shopify_collection',
        'external_id' => 'gid://shopify/Collection/777',
    ]);

    Http::fake([
        'test.myshopify.com/admin/api/2025-01/custom_collections/777.json' => Http::response(null, 200),
    ]);

    $job = new PushCollectionDeletedJob($collectionId, $this->account->id);
    $job->handle();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $collectionId)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('collection_delete');
});

test('PushCollectionDeletedJob returns early when no ExternalIdentifier exists', function () {
    Http::fake();

    $job = new PushCollectionDeletedJob(12345, $this->account->id);
    $job->handle();

    Http::assertNothingSent();
});

test('PushCollectionDeletedJob returns early when no active integration', function () {
    $otherAccount = Account::factory()->creator()->create();

    Http::fake();

    $job = new PushCollectionDeletedJob(1, $otherAccount->id);
    $job->handle();

    Http::assertNothingSent();
});

test('PushCollectionDeletedJob has retry configuration', function () {
    $job = new PushCollectionDeletedJob(1, 1);

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(60);
});
