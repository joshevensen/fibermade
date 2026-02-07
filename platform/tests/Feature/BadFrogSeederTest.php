<?php

use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use Database\Seeders\BadFrogSeeder;
use Database\Seeders\FoundationSeeder;

test('seeds 4 bases, 70 colorways, and 7 collections', function () {
    $this->seed(FoundationSeeder::class);
    $this->seed(BadFrogSeeder::class);

    expect(Base::count())->toBe(4);
    expect(Colorway::count())->toBe(70);
    expect(Collection::count())->toBe(7);
});

test('attaches 10 colorways to each collection', function () {
    $this->seed(FoundationSeeder::class);
    $this->seed(BadFrogSeeder::class);

    $collections = Collection::withCount('colorways')->get();

    expect($collections)->toHaveCount(7);
    foreach ($collections as $collection) {
        expect($collection->colorways_count)->toBe(10);
    }
});

test('does nothing when Bad Frog Yarn Co. creator is missing', function () {
    $this->seed(BadFrogSeeder::class);

    expect(Base::count())->toBe(0);
    expect(Colorway::count())->toBe(0);
    expect(Collection::count())->toBe(0);
});
