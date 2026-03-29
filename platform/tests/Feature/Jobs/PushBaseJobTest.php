<?php

use App\Enums\IntegrationLogStatus;
use App\Enums\IntegrationType;
use App\Jobs\PushBaseJob;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
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

// ─── handleCreated ────────────────────────────────────────────────────────────

test('handleCreated groups colorways by product and calls productVariantsBulkCreate', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);

    $colorway1 = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway1->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway2->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);

    $variantCounter = 0;
    Http::fake(function ($request) use (&$variantCounter) {
        $body = $request->body();

        if (str_contains($body, 'getLocations')) {
            return Http::response(['data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]]]);
        }

        $variantCounter++;

        return Http::response([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [['id' => "gid://shopify/ProductVariant/{$variantCounter}00"]],
                    'userErrors' => [],
                ],
            ],
        ]);
    });

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkCreate'));

    // ExternalIdentifiers created for both colorways
    expect(ExternalIdentifier::where('external_type', 'shopify_variant')->count())->toBe(2);
});

test('handleCreated creates ExternalIdentifier mappings for each new variant', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);

    Http::fake(function ($request) {
        $body = $request->body();

        if (str_contains($body, 'getLocations')) {
            return Http::response(['data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]]]);
        }

        return Http::response([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/555']],
                    'userErrors' => [],
                ],
            ],
        ]);
    });

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    $inventory = Inventory::where('base_id', $base->id)->where('colorway_id', $colorway->id)->first();
    expect($inventory)->not->toBeNull();

    $identifier = ExternalIdentifier::where('identifiable_type', Inventory::class)
        ->where('identifiable_id', $inventory->id)
        ->where('external_type', 'shopify_variant')
        ->first();
    expect($identifier)->not->toBeNull();
    expect($identifier->external_id)->toBe('gid://shopify/ProductVariant/555');
});

test('handleCreated logs success after creating variants', function () {
    Queue::fake();

    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);

    Http::fake(function ($request) {
        if (str_contains($request->body(), 'getLocations')) {
            return Http::response(['data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]]]);
        }

        return Http::response([
            'data' => [
                'productVariantsBulkCreate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/555']],
                    'userErrors' => [],
                ],
            ],
        ]);
    });

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $base->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('base_created');
    expect($log->metadata['count'])->toBe(1);
});

test('handleCreated calls updateVariantsBulk when variant already exists in Shopify', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Hopper Sock', 'retail_price' => 28.00, 'cost' => 10.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inventory = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/99',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/99']],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkUpdate'));
    Http::assertNotSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkCreate'));

    // No new ExternalIdentifier should be created — mapping already existed
    expect(ExternalIdentifier::where('external_type', 'shopify_variant')->count())->toBe(1);
});

test('handleCreated mixes create and update when some variants already exist', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'Fingering', 'retail_price' => 28.00, 'cost' => 10.00]);

    $colorway1 = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $this->account->id]);

    $inventory1 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway1->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway1->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway2->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);
    // colorway1's variant already mapped; colorway2's is not
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inventory1->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    Http::fake(function ($request) {
        $body = $request->body();

        if (str_contains($body, 'getLocations')) {
            return Http::response(['data' => ['locations' => ['edges' => [['node' => ['id' => 'gid://shopify/Location/1']]]]]]);
        }

        if (str_contains($body, 'productVariantsBulkCreate')) {
            return Http::response([
                'data' => [
                    'productVariantsBulkCreate' => [
                        'productVariants' => [['id' => 'gid://shopify/ProductVariant/20']],
                        'userErrors' => [],
                    ],
                ],
            ]);
        }

        return Http::response([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [['id' => 'gid://shopify/ProductVariant/10']],
                    'userErrors' => [],
                ],
            ],
        ]);
    });

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkUpdate'));
    Http::assertSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkCreate'));

    // One new ExternalIdentifier for the created variant (colorway2)
    expect(ExternalIdentifier::where('external_type', 'shopify_variant')->count())->toBe(2);
});

test('handleCreated skips colorways without Shopify product mapping', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    Colorway::factory()->create(['account_id' => $this->account->id]); // No ExternalIdentifier

    Http::fake();

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    Http::assertNothingSent();
    expect(ExternalIdentifier::where('external_type', 'shopify_variant')->count())->toBe(0);
});

// ─── handleUpdated ────────────────────────────────────────────────────────────

test('handleUpdated groups inventories by product and calls productVariantsBulkUpdate', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'DK', 'retail_price' => 32.00]);

    $colorway1 = Colorway::factory()->create(['account_id' => $this->account->id]);
    $colorway2 = Colorway::factory()->create(['account_id' => $this->account->id]);

    $inv1 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway1->id, 'base_id' => $base->id]);
    $inv2 = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway2->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway1->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway2->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/2',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv1->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv2->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/20',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new PushBaseJob($base, 'updated');
    $job->handle();

    Http::assertSent(fn ($r) => str_contains($r->body(), 'productVariantsBulkUpdate'));
});

test('handleUpdated logs success after updating variants', function () {
    Queue::fake();

    $base = Base::factory()->create(['account_id' => $this->account->id, 'descriptor' => 'DK', 'retail_price' => 32.00]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    $inv = Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);

    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Colorway::class,
        'identifiable_id' => $colorway->id,
        'external_type' => 'shopify_product',
        'external_id' => 'gid://shopify/Product/1',
    ]);
    ExternalIdentifier::create([
        'integration_id' => $this->integration->id,
        'identifiable_type' => Inventory::class,
        'identifiable_id' => $inv->id,
        'external_type' => 'shopify_variant',
        'external_id' => 'gid://shopify/ProductVariant/10',
    ]);

    Http::fake([
        'test.myshopify.com/*' => Http::response([
            'data' => [
                'productVariantsBulkUpdate' => [
                    'productVariants' => [],
                    'userErrors' => [],
                ],
            ],
        ]),
    ]);

    $job = new PushBaseJob($base, 'updated');
    $job->handle();

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('loggable_id', $base->id)
        ->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(IntegrationLogStatus::Success);
    expect($log->metadata['operation'])->toBe('base_updated');
    expect($log->metadata['count'])->toBe(1);
});

test('handleUpdated skips inventories without variant mapping', function () {
    $base = Base::factory()->create(['account_id' => $this->account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $this->account->id]);
    Inventory::factory()->create(['account_id' => $this->account->id, 'colorway_id' => $colorway->id, 'base_id' => $base->id]);
    // No ExternalIdentifier for variant

    Http::fake();

    $job = new PushBaseJob($base, 'updated');
    $job->handle();

    Http::assertNothingSent();
});

test('PushBaseJob returns early when no active integration', function () {
    $otherAccount = Account::factory()->creator()->create();
    $base = Base::factory()->create(['account_id' => $otherAccount->id]);

    Http::fake();

    $job = new PushBaseJob($base, 'created');
    $job->handle();

    Http::assertNothingSent();
});

test('PushBaseJob has retry configuration', function () {
    $job = new PushBaseJob(new Base, 'created');

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe(60);
});
