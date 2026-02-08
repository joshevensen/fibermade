<?php

use App\Http\Resources\Api\V1\BaseResource;
use App\Http\Resources\Api\V1\CollectionResource;
use App\Http\Resources\Api\V1\ColorwayResource;
use App\Http\Resources\Api\V1\InventoryResource;
use App\Models\Account;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Inventory;
use App\Models\Media;

test('ColorwayResource serializes core fields without relationships', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $colorway->unsetRelations();

    $resource = ColorwayResource::make($colorway);
    $array = $resource->response()->getData(true);

    expect($array)->toHaveKey('data')
        ->and($array['data'])->toHaveKeys([
            'id', 'name', 'description', 'technique', 'colors', 'per_pan', 'status',
            'created_at', 'updated_at',
        ])
        ->and($array['data']['colors'])->toBeArray()
        ->and($array['data'])->not->toHaveKey('collections')
        ->and($array['data'])->not->toHaveKey('inventories')
        ->and($array['data'])->not->toHaveKey('primary_image_url');
});

test('ColorwayResource includes collections inventories and primary_image_url when loaded', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $colorway->collections()->attach($collection->id);

    Media::create([
        'mediable_type' => Colorway::class,
        'mediable_id' => $colorway->id,
        'file_path' => 'colorways/test.jpg',
        'file_name' => 'test.jpg',
        'is_primary' => true,
    ]);

    $base = Base::factory()->create();
    Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $colorway->load(['collections', 'inventories', 'media']);

    $resource = ColorwayResource::make($colorway);
    $array = $resource->response()->getData(true);

    $data = $array['data'] ?? $array;
    expect($data)->toHaveKeys(['collections', 'inventories', 'primary_image_url'])
        ->and($data['collections']['data'] ?? $data['collections'])->toBeArray()
        ->and($data['inventories']['data'] ?? $data['inventories'])->toBeArray()
        ->and($data['primary_image_url'])->toBeString();
});

test('BaseResource serializes all fields with decimal strings', function () {
    $base = Base::factory()->create([
        'cost' => 12.50,
        'retail_price' => 25.00,
        'wool_percent' => 80.50,
    ]);

    $resource = BaseResource::make($base);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'descriptor', 'description', 'code', 'status', 'weight', 'size',
        'cost', 'retail_price', 'wool_percent', 'nylon_percent', 'alpaca_percent',
        'yak_percent', 'camel_percent', 'cotton_percent', 'bamboo_percent',
        'silk_percent', 'linen_percent', 'created_at', 'updated_at',
    ])
        ->and($data['cost'])->toBe('12.50')
        ->and($data['retail_price'])->toBe('25.00')
        ->and($data['wool_percent'])->toBe('80.50');
});

test('CollectionResource excludes colorways when not loaded', function () {
    $account = Account::factory()->create();
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $collection->unsetRelations();

    $resource = CollectionResource::make($collection);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys(['id', 'name', 'description', 'status', 'created_at', 'updated_at'])
        ->and($data)->not->toHaveKey('colorways');
});

test('CollectionResource includes colorways when loaded', function () {
    $account = Account::factory()->create();
    $collection = Collection::factory()->create(['account_id' => $account->id]);
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $collection->colorways()->attach($colorway->id);

    $collection->load('colorways');

    $resource = CollectionResource::make($collection);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKey('colorways')
        ->and($data['colorways']['data'] ?? $data['colorways'])->toBeArray();
});

test('InventoryResource excludes colorway and base when not loaded', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create();
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);
    $inventory->unsetRelations();

    $resource = InventoryResource::make($inventory);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys([
        'id', 'colorway_id', 'base_id', 'quantity', 'created_at', 'updated_at',
    ])
        ->and($data)->not->toHaveKey('colorway')
        ->and($data)->not->toHaveKey('base');
});

test('InventoryResource includes colorway and base when loaded', function () {
    $account = Account::factory()->create();
    $colorway = Colorway::factory()->create(['account_id' => $account->id]);
    $base = Base::factory()->create();
    $inventory = Inventory::factory()->create([
        'account_id' => $account->id,
        'colorway_id' => $colorway->id,
        'base_id' => $base->id,
    ]);

    $inventory->load(['colorway', 'base']);

    $resource = InventoryResource::make($inventory);
    $array = $resource->response()->getData(true);
    $data = $array['data'] ?? $array;

    expect($data)->toHaveKeys(['colorway', 'base'])
        ->and($data['colorway'])->toBeArray()
        ->and($data['base'])->toBeArray();
});
