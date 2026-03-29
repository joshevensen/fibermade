<?php

use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Enums\Technique;
use App\Jobs\PushBaseDeletedJob;
use App\Jobs\PushBaseJob;
use App\Jobs\PushColorwayImagesJob;
use App\Jobs\PushColorwayJob;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Media;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('services.shopify.catalog_sync_enabled', true);
    Queue::fake();
});

test('Colorway name change dispatches PushColorwayJob', function () {
    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id, 'name' => 'Original Name']);

    $colorway->update(['name' => 'Updated Name']);

    Queue::assertPushed(PushColorwayJob::class, function ($job) use ($colorway) {
        return $job->colorway->id === $colorway->id;
    });
});

test('Colorway catalog field changes dispatch PushColorwayJob', function () {
    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create([
        'account_id' => $account->id,
        'status' => ColorwayStatus::Active,
        'technique' => Technique::Solid,
    ]);

    $colorway->update(['description' => 'New description']);
    Queue::assertPushed(PushColorwayJob::class);

    Queue::fake();
    $colorway->update(['status' => ColorwayStatus::Retired]);
    Queue::assertPushed(PushColorwayJob::class);

    Queue::fake();
    $colorway->update(['technique' => Technique::Variegated]);
    Queue::assertPushed(PushColorwayJob::class);
});

test('Base descriptor change dispatches PushBaseJob', function () {
    $account = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $account->id, 'descriptor' => 'Fingering']);

    $base->update(['descriptor' => 'DK']);

    Queue::assertPushed(PushBaseJob::class, function ($job) use ($base) {
        return $job->base->id === $base->id && $job->action === 'updated';
    });
});

test('Base retail_price change dispatches PushBaseJob', function () {
    $account = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $account->id, 'retail_price' => 2800]);

    $base->update(['retail_price' => 3200]);

    Queue::assertPushed(PushBaseJob::class, function ($job) use ($base) {
        return $job->base->id === $base->id && $job->action === 'updated';
    });
});

test('Base creation dispatches PushBaseJob with action created', function () {
    $account = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $account->id]);

    Queue::assertPushed(PushBaseJob::class, function ($job) use ($base) {
        return $job->base->id === $base->id && $job->action === 'created';
    });
});

test('Base deletion dispatches PushBaseDeletedJob', function () {
    $account = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $account->id]);
    $baseId = $base->id;
    $accountId = $base->account_id;

    $base->delete();

    Queue::assertPushed(PushBaseDeletedJob::class, function ($job) use ($baseId, $accountId) {
        return $job->baseId === $baseId && $job->accountId === $accountId;
    });
});

test('Colorway image changes dispatch PushColorwayImagesJob', function () {
    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $media = Media::create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/test.jpg',
        'file_name' => 'test.jpg',
        'is_primary' => false,
    ]);

    Queue::fake();
    $media->update(['is_primary' => true]);

    Queue::assertPushed(PushColorwayImagesJob::class, function ($job) use ($colorway) {
        return $job->colorway->id === $colorway->id;
    });
});

test('observer dispatches job for base affecting multiple products', function () {
    $account = Account::factory()->creator()->create();
    $integration = Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
    $base = Base::factory()->create(['account_id' => $account->id, 'descriptor' => 'Fingering']);
    $colorways = Colorway::factory()->count(3)->create(['account_id' => $account->id]);
    foreach ($colorways as $colorway) {
        ExternalIdentifier::create([
            'integration_id' => $integration->id,
            'identifiable_type' => Colorway::class,
            'identifiable_id' => $colorway->id,
            'external_type' => 'shopify_product',
            'external_id' => 'gid://shopify/Product/'.($colorway->id + 1000),
        ]);
    }

    $base->update(['descriptor' => 'Sport']);

    Queue::assertPushed(PushBaseJob::class);
});

test('observer error does not block model save', function () {
    Config::set('services.shopify.catalog_sync_enabled', true);
    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id, 'name' => 'Before']);
    $originalId = $colorway->id;

    PushColorwayJob::dispatchSync($colorway);
    Queue::fake();

    $colorway->update(['name' => 'After']);

    expect($colorway->fresh()->name)->toBe('After');
    expect($colorway->fresh()->id)->toBe($originalId);
});

test('observer does not dispatch when catalog_sync_enabled is false', function () {
    Config::set('services.shopify.catalog_sync_enabled', false);
    Queue::fake();

    $account = Account::factory()->creator()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $colorway->update(['name' => 'New Name']);

    Queue::assertNotPushed(PushColorwayJob::class);
});

test('ColorwayObserver created dispatches PushColorwayJob with action created', function () {
    $account = Account::factory()->creator()->create();
    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    $colorway = Colorway::factory()->create(['account_id' => $account->id]);

    Queue::assertPushed(PushColorwayJob::class, function ($job) use ($colorway) {
        return $job->colorway->id === $colorway->id && $job->action === 'created';
    });
});

test('ColorwayObserver created does not dispatch when no Shopify integration exists', function () {
    $account = Account::factory()->creator()->create();

    Colorway::factory()->create(['account_id' => $account->id]);

    Queue::assertNotPushed(PushColorwayJob::class);
});

test('ColorwayObserver created does not dispatch when catalog sync is disabled', function () {
    Config::set('services.shopify.catalog_sync_enabled', false);

    $account = Account::factory()->creator()->create();
    Integration::factory()->create([
        'account_id' => $account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);

    Colorway::factory()->create(['account_id' => $account->id]);

    Queue::assertNotPushed(PushColorwayJob::class);
});
