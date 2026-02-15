<?php

use App\Enums\IntegrationType;
use App\Models\Account;
use App\Models\Base;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\IntegrationLog;
use App\Models\Inventory;
use App\Services\ImportService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->account = Account::factory()->creator()->create();
    $this->integration = Integration::factory()->create([
        'account_id' => $this->account->id,
        'type' => IntegrationType::Shopify,
        'active' => true,
        'credentials' => json_encode(['access_token' => 'test-token']),
        'settings' => ['shop' => 'test.myshopify.com'],
    ]);
});

function createProductsCsv(array $rows): UploadedFile
{
    $headers = ['Handle', 'Title', 'Body (HTML)', 'Vendor', 'Type', 'Tags', 'Status', 'Option1 Name', 'Option1 Value', 'Variant Price', 'Variant SKU', 'Variant ID', 'Variant Inventory Qty'];
    $lines = [implode(',', array_map(fn ($h) => "\"$h\"", $headers))];
    foreach ($rows as $row) {
        $line = [];
        foreach ($headers as $h) {
            $line[] = '"'.($row[$h] ?? '').'"';
        }
        $lines[] = implode(',', $line);
    }
    $content = implode("\n", $lines);

    $tmp = tempnam(sys_get_temp_dir(), 'import_test_');
    file_put_contents($tmp, $content);

    return new UploadedFile($tmp, 'products.csv', 'text/csv', null, true);
}

test('importProducts pulls actual inventory quantities from CSV', function () {
    $csv = createProductsCsv([
        ['Handle' => 'mountain-mist', 'Title' => 'Mountain Mist', 'Option1 Value' => 'Fingering', 'Variant Price' => '28', 'Variant ID' => '40123456789', 'Variant Inventory Qty' => '15'],
    ]);

    $service = new ImportService;
    $result = $service->importProducts($csv, $this->account->id);

    expect($result['success'])->toBeTrue();
    $inventory = Inventory::where('account_id', $this->account->id)->first();
    expect($inventory)->not->toBeNull()
        ->and($inventory->quantity)->toBe(15);
});

test('importProducts creates Inventory to Variant ExternalIdentifier when variant_id present', function () {
    $csv = createProductsCsv([
        ['Handle' => 'ocean-breeze', 'Title' => 'Ocean Breeze', 'Option1 Value' => 'Worsted', 'Variant Price' => '32', 'Variant ID' => '40987654321', 'Variant Inventory Qty' => '8'],
    ]);

    $service = new ImportService;
    $service->importProducts($csv, $this->account->id);

    $inventory = Inventory::where('account_id', $this->account->id)->first();
    expect($inventory)->not->toBeNull();

    $identifier = ExternalIdentifier::where('integration_id', $this->integration->id)
        ->where('external_type', 'shopify_variant')
        ->where('identifiable_type', Inventory::class)
        ->where('identifiable_id', $inventory->id)
        ->first();

    expect($identifier)->not->toBeNull()
        ->and($identifier->external_id)->toContain('40987654321');
});

test('importProducts deduplicates bases across products', function () {
    $csv = createProductsCsv([
        ['Handle' => 'product-a', 'Title' => 'Product A', 'Option1 Value' => 'Fingering', 'Variant Price' => '28', 'Variant Inventory Qty' => '5'],
        ['Handle' => 'product-a', 'Title' => 'Product A', 'Option1 Value' => 'DK', 'Variant Price' => '30', 'Variant Inventory Qty' => '3'],
        ['Handle' => 'product-b', 'Title' => 'Product B', 'Option1 Value' => 'Fingering', 'Variant Price' => '28', 'Variant Inventory Qty' => '10'],
    ]);

    $service = new ImportService;
    $service->importProducts($csv, $this->account->id);

    $bases = Base::where('account_id', $this->account->id)->get();
    expect($bases->count())->toBe(2);

    $fingeringBases = $bases->where('descriptor', 'Fingering');
    expect($fingeringBases->count())->toBe(1);

    $inventories = Inventory::where('account_id', $this->account->id)
        ->where('base_id', $fingeringBases->first()->id)
        ->get();
    expect($inventories->count())->toBe(2);
});

test('importProducts detects base price conflicts and logs warnings', function () {
    $csv = createProductsCsv([
        ['Handle' => 'mountain-mist', 'Title' => 'Mountain Mist', 'Option1 Value' => 'Fingering', 'Variant Price' => '28', 'Variant Inventory Qty' => '5'],
        ['Handle' => 'ocean-breeze', 'Title' => 'Ocean Breeze', 'Option1 Value' => 'Fingering', 'Variant Price' => '32', 'Variant Inventory Qty' => '8'],
    ]);

    $service = new ImportService;
    $result = $service->importProducts($csv, $this->account->id);

    expect($result['success'])->toBeTrue()
        ->and($result['warnings'])->not->toBeEmpty();
    expect(collect($result['warnings'])->first())->toContain('conflicting price');

    $base = Base::where('account_id', $this->account->id)->where('descriptor', 'Fingering')->first();
    expect((float) $base->retail_price)->toBe(28.0);

    $log = IntegrationLog::where('integration_id', $this->integration->id)
        ->where('message', 'Import completed with price conflict warnings')
        ->first();
    expect($log)->not->toBeNull();
});

test('importProducts handles missing variant_id gracefully', function () {
    $csv = createProductsCsv([
        ['Handle' => 'no-variant-id', 'Title' => 'No Variant ID', 'Option1 Value' => 'Fingering', 'Variant Price' => '28', 'Variant Inventory Qty' => '5'],
    ]);

    $service = new ImportService;
    $result = $service->importProducts($csv, $this->account->id);

    expect($result['success'])->toBeTrue();

    $inventory = Inventory::where('account_id', $this->account->id)->first();
    expect($inventory)->not->toBeNull()
        ->and($inventory->quantity)->toBe(5);

    $identifier = ExternalIdentifier::where('integration_id', $this->integration->id)
        ->where('external_type', 'shopify_variant')
        ->where('identifiable_type', Inventory::class)
        ->where('identifiable_id', $inventory->id)
        ->first();
    expect($identifier)->toBeNull();
});
