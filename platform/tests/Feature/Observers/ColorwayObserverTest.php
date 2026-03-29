<?php

use App\Enums\IntegrationType;
use App\Jobs\PushColorwayJob;
use App\Models\Account;
use App\Models\Colorway;
use App\Models\Integration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Config::set('services.shopify.catalog_sync_enabled', true);

    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

test('deleting a colorway dispatches PushColorwayJob with deleted action', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    Queue::fake(); // reset after create dispatch

    $colorway->delete();

    Queue::assertPushed(PushColorwayJob::class, function ($job) use ($colorway) {
        return $job->colorway->id === $colorway->id && $job->action === 'deleted';
    });
});

test('deleting a colorway does not dispatch job when no active Shopify integration', function () {
    $this->integration->update(['active' => false]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    Queue::fake();

    $colorway->delete();

    Queue::assertNotPushed(PushColorwayJob::class);
});

test('deleting a colorway does not dispatch job when catalog sync is globally disabled', function () {
    Config::set('services.shopify.catalog_sync_enabled', false);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    Queue::fake();

    $colorway->delete();

    Queue::assertNotPushed(PushColorwayJob::class);
});

test('creating a colorway dispatches PushColorwayJob with created action', function () {
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    Queue::assertPushed(PushColorwayJob::class, function ($job) use ($colorway) {
        return $job->colorway->id === $colorway->id && $job->action === 'created';
    });
});
