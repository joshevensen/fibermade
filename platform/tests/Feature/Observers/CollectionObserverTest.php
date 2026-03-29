<?php

use App\Enums\IntegrationType;
use App\Jobs\PushCollectionDeletedJob;
use App\Jobs\PushCollectionJob;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Integration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('services.shopify.catalog_sync_enabled', true);
    Queue::fake();

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

test('CollectionObserver created dispatches PushCollectionJob with action created', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);

    Queue::assertPushed(PushCollectionJob::class, function ($job) use ($collection) {
        return $job->collection->id === $collection->id && $job->action === 'created';
    });
});

test('CollectionObserver updated dispatches PushCollectionJob with action updated', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    Queue::fake();

    $collection->update(['name' => 'Updated Name']);

    Queue::assertPushed(PushCollectionJob::class, function ($job) use ($collection) {
        return $job->collection->id === $collection->id && $job->action === 'updated';
    });
});

test('CollectionObserver deleted dispatches PushCollectionDeletedJob', function () {
    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    Queue::fake();

    $collectionId = $collection->id;
    $collection->delete();

    Queue::assertPushed(PushCollectionDeletedJob::class, function ($job) use ($collectionId) {
        return $job->collectionId === $collectionId;
    });
});

test('CollectionObserver does not dispatch when no active Shopify integration', function () {
    $otherAccount = Account::factory()->creator()->create();
    $collection = Collection::factory()->create(['account_id' => $otherAccount->id]);

    Queue::assertNotPushed(PushCollectionJob::class);
});

test('CollectionObserver does not dispatch when catalog sync is disabled', function () {
    Config::set('services.shopify.catalog_sync_enabled', false);
    Queue::fake();

    Collection::factory()->create(['account_id' => $this->account->id]);

    Queue::assertNotPushed(PushCollectionJob::class);
});

test('CollectionObserver deleted does not dispatch when no active Shopify integration', function () {
    $otherAccount = Account::factory()->creator()->create();
    $collection = Collection::factory()->create(['account_id' => $otherAccount->id]);
    Queue::fake();

    $collection->delete();

    Queue::assertNotPushed(PushCollectionDeletedJob::class);
});

test('CollectionObserver deleted does not dispatch when catalog sync is disabled', function () {
    Config::set('services.shopify.catalog_sync_enabled', false);
    Queue::fake();

    $collection = Collection::factory()->create(['account_id' => $this->account->id]);
    $collection->delete();

    Queue::assertNotPushed(PushCollectionDeletedJob::class);
});
