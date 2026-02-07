<?php

namespace App\Services;

use App\Enums\BaseStatus;
use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\IntegrationType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\Weight;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Customer;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ImportService
{
    /**
     * Import products from CSV files.
     *
     * @return array{success: bool, colorways_created: int, colorways_updated: int, bases_created: int, errors: array<string>}
     */
    public function importProducts(UploadedFile $productsFile, int $accountId): array
    {
        $result = [
            'success' => true,
            'colorways_created' => 0,
            'colorways_updated' => 0,
            'bases_created' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // Get or create Shopify integration
            $integration = $this->getOrCreateShopifyIntegration($accountId);

            // Parse products CSV
            $productsData = $this->parseCsv($productsFile);
            if (empty($productsData)) {
                throw new \Exception('Products CSV file is empty or invalid.');
            }

            // Process products to create/update Colorways and Bases
            $colorwayMap = [];
            $baseMap = [];

            // First pass: Process product rows to create/update Colorways
            foreach ($productsData as $row) {
                try {
                    if (! CsvColumnMapper::isProductRow($row)) {
                        continue;
                    }

                    $handle = CsvColumnMapper::getValue($row, 'handle');
                    $title = CsvColumnMapper::getValue($row, 'title');

                    if (! $handle && ! $title) {
                        continue;
                    }

                    $colorway = $this->getOrCreateColorway($accountId, $handle, $title, $row, $integration);
                    if ($handle) {
                        $colorwayMap[$handle] = $colorway;
                    }
                    if ($title) {
                        $colorwayMap[$title] = $colorway;
                    }

                    if ($colorway->wasRecentlyCreated) {
                        $result['colorways_created']++;
                    } else {
                        $result['colorways_updated']++;
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing product row: {$e->getMessage()}";
                }
            }

            // Second pass: Process variant rows to create/update Bases
            foreach ($productsData as $row) {
                try {
                    if (! CsvColumnMapper::isVariantRow($row)) {
                        continue;
                    }

                    $handle = CsvColumnMapper::getValue($row, 'handle');
                    $option1Value = CsvColumnMapper::getValue($row, 'option1_value');

                    if (! $handle || ! $option1Value) {
                        continue;
                    }

                    // Find colorway
                    $colorway = $colorwayMap[$handle] ?? null;
                    if (! $colorway) {
                        $colorway = Colorway::where('account_id', $accountId)
                            ->whereHas('externalIdentifiers', function ($query) use ($integration, $handle) {
                                $query->where('integration_id', $integration->id)
                                    ->where('external_type', 'shopify_product')
                                    ->where('external_id', $handle);
                            })
                            ->first();
                    }

                    // If colorway still doesn't exist, create it from the variant row
                    // This handles cases where the CSV is missing the Product row
                    if (! $colorway) {
                        $title = CsvColumnMapper::getValue($row, 'title');
                        if ($handle && $title) {
                            $colorway = $this->getOrCreateColorway($accountId, $handle, $title, $row, $integration);
                            // Add to map so we don't recreate it for other variants of the same product
                            $colorwayMap[$handle] = $colorway;
                            if ($title) {
                                $colorwayMap[$title] = $colorway;
                            }
                            if ($colorway->wasRecentlyCreated) {
                                $result['colorways_created']++;
                            } else {
                                $result['colorways_updated']++;
                            }
                        }
                    }

                    if (! $colorway) {
                        continue;
                    }

                    // Create or update base
                    $base = $this->getOrCreateBaseFromVariant($accountId, $option1Value, $row, $integration);
                    $baseKey = $base->descriptor.'-'.$base->weight->value;
                    $baseMap[$baseKey] = $base;

                    if ($base->wasRecentlyCreated) {
                        $result['bases_created']++;
                    }

                    // Create inventory entry for this colorway-base combination
                    Inventory::firstOrCreate(
                        [
                            'account_id' => $accountId,
                            'colorway_id' => $colorway->id,
                            'base_id' => $base->id,
                        ],
                        [
                            'quantity' => 0,
                        ]
                    );
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing variant row: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Import orders from CSV file.
     *
     * @return array{success: bool, orders_created: int, orders_updated: int, order_items_created: int, errors: array<string>}
     */
    public function importOrders(UploadedFile $ordersFile, int $accountId): array
    {
        $result = [
            'success' => true,
            'orders_created' => 0,
            'orders_updated' => 0,
            'order_items_created' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // Get or create Shopify integration
            $integration = $this->getOrCreateShopifyIntegration($accountId);

            // Parse orders CSV
            $ordersData = $this->parseCsv($ordersFile);
            if (empty($ordersData)) {
                throw new \Exception('Orders CSV file is empty or invalid.');
            }

            // Group rows by order ID
            $ordersByExternalId = [];
            foreach ($ordersData as $row) {
                $orderId = $row['Id'] ?? null;
                if ($orderId) {
                    if (! isset($ordersByExternalId[$orderId])) {
                        $ordersByExternalId[$orderId] = [];
                    }
                    $ordersByExternalId[$orderId][] = $row;
                }
            }

            // Process each order
            foreach ($ordersByExternalId as $externalOrderId => $orderRows) {
                try {
                    $firstRow = $orderRows[0];

                    // Find customer by email
                    $email = $firstRow['Email'] ?? null;
                    $customer = null;
                    if ($email) {
                        $customer = Customer::where('account_id', $accountId)
                            ->where('email', $email)
                            ->first();
                    }

                    if (! $customer) {
                        $result['errors'][] = "Customer not found for order {$externalOrderId} (email: {$email})";

                        continue;
                    }

                    // Parse order date
                    $orderDate = null;
                    if (isset($firstRow['Created at'])) {
                        try {
                            $orderDate = \Carbon\Carbon::parse($firstRow['Created at'])->toDateString();
                        } catch (\Exception $e) {
                            $orderDate = now()->toDateString();
                        }
                    } else {
                        $orderDate = now()->toDateString();
                    }

                    // Determine order status
                    $financialStatus = $firstRow['Financial Status'] ?? '';
                    $fulfillmentStatus = $firstRow['Fulfillment Status'] ?? '';
                    $status = $this->mapOrderStatus($financialStatus, $fulfillmentStatus);

                    // Calculate tax amount (sum of all tax values)
                    $taxAmount = 0;
                    for ($i = 1; $i <= 5; $i++) {
                        $taxValue = $firstRow["Tax {$i} Value"] ?? 0;
                        $taxAmount += (float) $taxValue;
                    }

                    // Create or update order
                    $order = Order::updateOrCreate(
                        [
                            'account_id' => $accountId,
                            'orderable_type' => Customer::class,
                            'orderable_id' => $customer->id,
                        ],
                        [
                            'type' => OrderType::Retail,
                            'status' => $status,
                            'order_date' => $orderDate,
                            'subtotal_amount' => (float) ($firstRow['Subtotal'] ?? 0),
                            'shipping_amount' => (float) ($firstRow['Shipping'] ?? 0),
                            'discount_amount' => (float) ($firstRow['Discount Amount'] ?? 0),
                            'tax_amount' => $taxAmount,
                            'total_amount' => (float) ($firstRow['Total'] ?? 0),
                            'notes' => $this->buildOrderNotes($firstRow),
                        ]
                    );

                    if ($order->wasRecentlyCreated) {
                        $result['orders_created']++;
                    } else {
                        $result['orders_updated']++;
                    }

                    // Create external identifier for order
                    ExternalIdentifier::firstOrCreate(
                        [
                            'integration_id' => $integration->id,
                            'identifiable_type' => Order::class,
                            'identifiable_id' => $order->id,
                            'external_type' => 'shopify_order',
                            'external_id' => (string) $externalOrderId,
                        ]
                    );

                    // Process order items
                    foreach ($orderRows as $itemRow) {
                        try {
                            $lineItemName = $itemRow['Lineitem name'] ?? null;
                            if (! $lineItemName) {
                                continue;
                            }

                            // Parse line item name to find Colorway and Base
                            [$colorwayName, $baseDescriptor] = $this->parseLineItemName($lineItemName);

                            $colorway = Colorway::where('account_id', $accountId)
                                ->where('name', $colorwayName)
                                ->first();

                            if (! $colorway) {
                                $result['errors'][] = "Colorway not found: {$colorwayName}";

                                continue;
                            }

                            $base = $this->findBaseByDescriptor($accountId, $baseDescriptor);
                            if (! $base) {
                                $result['errors'][] = "Base not found: {$baseDescriptor}";

                                continue;
                            }

                            $quantity = (int) ($itemRow['Lineitem quantity'] ?? 0);
                            $unitPrice = (float) ($itemRow['Lineitem price'] ?? 0);
                            $lineTotal = $quantity * $unitPrice;

                            OrderItem::create([
                                'order_id' => $order->id,
                                'colorway_id' => $colorway->id,
                                'base_id' => $base->id,
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'line_total' => $lineTotal,
                            ]);

                            $result['order_items_created']++;
                        } catch (\Exception $e) {
                            $result['errors'][] = "Error processing order item: {$e->getMessage()}";
                        }
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing order {$externalOrderId}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Import collections from CSV file.
     *
     * @return array{success: bool, collections_created: int, collections_updated: int, colorways_linked: int, errors: array<string>}
     */
    public function importCollections(UploadedFile $collectionsFile, int $accountId): array
    {
        $result = [
            'success' => true,
            'collections_created' => 0,
            'collections_updated' => 0,
            'colorways_linked' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // Get or create Shopify integration
            $integration = $this->getOrCreateShopifyIntegration($accountId);

            // Parse collections CSV
            $collectionsData = $this->parseCsv($collectionsFile);
            if (empty($collectionsData)) {
                throw new \Exception('Collections CSV file is empty or invalid.');
            }

            // Group rows by collection ID (collections have same ID, products are linked via productHandle)
            $collectionsById = [];

            foreach ($collectionsData as $row) {
                $collectionId = CsvColumnMapper::getValue($row, 'collection_id');
                $collectionTitle = CsvColumnMapper::getValue($row, 'collection_title');
                $collectionHandle = CsvColumnMapper::getValue($row, 'collection_handle');
                $productHandle = CsvColumnMapper::getValue($row, 'product_handle');

                if (! $collectionId) {
                    continue;
                }

                // Initialize collection if not exists
                if (! isset($collectionsById[$collectionId])) {
                    $collectionsById[$collectionId] = [
                        'id' => $collectionId,
                        'title' => $collectionTitle,
                        'handle' => $collectionHandle,
                        'description' => CsvColumnMapper::getValue($row, 'collection_description'),
                        'products' => [],
                    ];
                }

                // Add product if this row has a product handle
                if ($productHandle) {
                    $collectionsById[$collectionId]['products'][] = $productHandle;
                }
            }

            // Process each collection
            foreach ($collectionsById as $collectionData) {
                try {
                    $collection = Collection::updateOrCreate(
                        [
                            'account_id' => $accountId,
                            'name' => $collectionData['title'] ?: $collectionData['handle'],
                        ],
                        [
                            'description' => $this->cleanHtml($collectionData['description']),
                            'status' => BaseStatus::Active,
                        ]
                    );

                    if ($collection->wasRecentlyCreated) {
                        $result['collections_created']++;
                    } else {
                        $result['collections_updated']++;
                    }

                    // Link colorways to collection
                    foreach ($collectionData['products'] as $productHandle) {
                        $colorway = Colorway::where('account_id', $accountId)
                            ->whereHas('externalIdentifiers', function ($query) use ($integration, $productHandle) {
                                $query->where('integration_id', $integration->id)
                                    ->where('external_type', 'shopify_product')
                                    ->where('external_id', $productHandle);
                            })
                            ->first();

                        if ($colorway) {
                            // Check if already attached
                            $exists = $collection->colorways()
                                ->where('colorway_id', $colorway->id)
                                ->exists();

                            if (! $exists) {
                                $collection->colorways()->attach($colorway->id);
                                $result['colorways_linked']++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing collection {$collectionData['handle']}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Import customers from CSV file.
     *
     * @return array{success: bool, customers_created: int, customers_updated: int, errors: array<string>}
     */
    public function importCustomers(UploadedFile $customersFile, int $accountId): array
    {
        $result = [
            'success' => true,
            'customers_created' => 0,
            'customers_updated' => 0,
            'errors' => [],
        ];

        try {
            DB::beginTransaction();

            // Get or create Shopify integration
            $integration = $this->getOrCreateShopifyIntegration($accountId);

            // Parse customers CSV
            $customersData = $this->parseCsv($customersFile);
            if (empty($customersData)) {
                throw new \Exception('Customers CSV file is empty or invalid.');
            }

            foreach ($customersData as $row) {
                try {
                    $email = $row['Email'] ?? null;
                    if (! $email) {
                        continue;
                    }

                    // Combine first and last name
                    $firstName = $row['First Name'] ?? '';
                    $lastName = $row['Last Name'] ?? '';
                    $name = trim("{$firstName} {$lastName}");

                    // Use phone from default address, fallback to phone field
                    $phone = $row['Default Address Phone'] ?? $row['Phone'] ?? null;

                    // Create or update customer
                    $customer = Customer::updateOrCreate(
                        [
                            'account_id' => $accountId,
                            'email' => $email,
                        ],
                        [
                            'name' => $name ?: $email,
                            'phone' => $phone,
                            'address_line1' => $row['Default Address Address1'] ?? null,
                            'address_line2' => $row['Default Address Address2'] ?? null,
                            'city' => $row['Default Address City'] ?? null,
                            'state_region' => $row['Default Address Province Code'] ?? null,
                            'postal_code' => $row['Default Address Zip'] ?? null,
                            'country_code' => $row['Default Address Country Code'] ?? null,
                            'notes' => $row['Note'] ?? null,
                        ]
                    );

                    if ($customer->wasRecentlyCreated) {
                        $result['customers_created']++;
                    } else {
                        $result['customers_updated']++;
                    }

                    // Create external identifier for customer
                    $customerId = $row['Customer ID'] ?? null;
                    if ($customerId) {
                        ExternalIdentifier::firstOrCreate(
                            [
                                'integration_id' => $integration->id,
                                'identifiable_type' => Customer::class,
                                'identifiable_id' => $customer->id,
                                'external_type' => 'shopify_customer',
                                'external_id' => (string) $customerId,
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing customer: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['success'] = false;
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Parse CSV file into array of associative arrays.
     *
     * @return array<int, array<string, string>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $data = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return [];
        }

        // Normalize headers (trim whitespace)
        $headers = array_map('trim', $headers);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($headers)) {
                continue;
            }

            $data[] = array_combine($headers, $row);
        }

        fclose($handle);

        return $data;
    }

    /**
     * Get or create Shopify integration for account.
     */
    private function getOrCreateShopifyIntegration(int $accountId): Integration
    {
        return Integration::firstOrCreate(
            [
                'account_id' => $accountId,
                'type' => IntegrationType::Shopify,
            ],
            [
                'credentials' => '',
                'settings' => [],
                'active' => true,
            ]
        );
    }

    /**
     * Get or create Colorway from product data.
     */
    private function getOrCreateColorway(int $accountId, ?string $handle, ?string $title, array $row, Integration $integration): Colorway
    {
        // Try to find by external identifier first
        $colorway = null;
        if ($handle) {
            $externalId = ExternalIdentifier::where('integration_id', $integration->id)
                ->where('external_type', 'shopify_product')
                ->where('external_id', $handle)
                ->where('identifiable_type', Colorway::class)
                ->first();

            if ($externalId) {
                $colorway = Colorway::find($externalId->identifiable_id);
            }
        }

        // Try to find by name
        if (! $colorway && $title) {
            $colorway = Colorway::where('account_id', $accountId)
                ->where('name', $title)
                ->first();
        }

        // Get status from row
        $statusValue = strtolower(trim(CsvColumnMapper::getValue($row, 'status') ?? 'active'));
        $status = match ($statusValue) {
            'active' => ColorwayStatus::Active,
            'archived' => ColorwayStatus::Retired,
            'draft' => ColorwayStatus::Idea,
            default => ColorwayStatus::Retired,
        };

        // Create if not found
        if (! $colorway) {
            $colorway = Colorway::create([
                'account_id' => $accountId,
                'name' => $title ?: $handle ?: 'Unknown',
                'description' => $this->cleanHtml(CsvColumnMapper::getValue($row, 'description')),
                'status' => $status,
                'colors' => $this->parseColors(CsvColumnMapper::getValue($row, 'tags')), // Use tags as colors fallback
                'per_pan' => 2, // Default to 2 if not specified in import
            ]);
        } else {
            // Update existing
            $colorway->update([
                'description' => $this->cleanHtml(CsvColumnMapper::getValue($row, 'description')) ?: $colorway->description,
                'status' => $status,
            ]);
        }

        // Create external identifier if handle exists
        if ($handle) {
            ExternalIdentifier::firstOrCreate(
                [
                    'integration_id' => $integration->id,
                    'identifiable_type' => Colorway::class,
                    'identifiable_id' => $colorway->id,
                    'external_type' => 'shopify_product',
                    'external_id' => $handle,
                ]
            );
        }

        return $colorway;
    }

    /**
     * Get or create Base from variant data.
     */
    private function getOrCreateBaseFromVariant(int $accountId, string $optionValue, array $row, Integration $integration): Base
    {
        [$descriptor, $weight] = $this->parseBaseOption($optionValue);

        $base = Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->where('weight', $weight)
            ->first();

        $statusValue = strtolower(trim(CsvColumnMapper::getValue($row, 'status') ?? 'active'));
        $status = match ($statusValue) {
            'active' => BaseStatus::Active,
            'archived' => BaseStatus::Retired,
            default => BaseStatus::Active,
        };

        if (! $base) {
            $base = Base::create([
                'account_id' => $accountId,
                'descriptor' => $descriptor,
                'weight' => $weight,
                'retail_price' => (float) (CsvColumnMapper::getValue($row, 'variant_price') ?? 0),
                'cost' => (float) (CsvColumnMapper::getValue($row, 'cost') ?? 0),
                'status' => $status,
            ]);
        } else {
            $base->update([
                'retail_price' => (float) (CsvColumnMapper::getValue($row, 'variant_price') ?? $base->retail_price),
                'cost' => (float) (CsvColumnMapper::getValue($row, 'cost') ?? $base->cost),
                'status' => $status,
            ]);
        }

        // Create external identifier for variant SKU if present
        $variantSku = CsvColumnMapper::getValue($row, 'variant_sku');
        if ($variantSku) {
            ExternalIdentifier::firstOrCreate(
                [
                    'integration_id' => $integration->id,
                    'identifiable_type' => Base::class,
                    'identifiable_id' => $base->id,
                    'external_type' => 'shopify_variant',
                    'external_id' => $variantSku,
                ]
            );
        }

        return $base;
    }

    /**
     * Get or create Base from option value (legacy method for inventory CSV).
     */
    private function getOrCreateBase(int $accountId, string $optionValue, array $row, Integration $integration): Base
    {
        [$descriptor, $weight] = $this->parseBaseOption($optionValue);

        $base = Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->where('weight', $weight)
            ->first();

        $statusValue = strtolower(trim(CsvColumnMapper::getValue($row, 'status') ?? 'active'));
        $status = match ($statusValue) {
            'active' => BaseStatus::Active,
            'archived' => BaseStatus::Retired,
            default => BaseStatus::Active,
        };

        if (! $base) {
            $base = Base::create([
                'account_id' => $accountId,
                'descriptor' => $descriptor,
                'weight' => $weight,
                'retail_price' => (float) (CsvColumnMapper::getValue($row, 'variant_price') ?? 0),
                'cost' => (float) (CsvColumnMapper::getValue($row, 'cost') ?? 0),
                'status' => $status,
            ]);
        } else {
            $base->update([
                'retail_price' => (float) (CsvColumnMapper::getValue($row, 'variant_price') ?? $base->retail_price),
                'cost' => (float) (CsvColumnMapper::getValue($row, 'cost') ?? $base->cost),
                'status' => $status,
            ]);
        }

        // Create external identifier for variant SKU if present
        $variantSku = CsvColumnMapper::getValue($row, 'variant_sku');
        if ($variantSku) {
            ExternalIdentifier::firstOrCreate(
                [
                    'integration_id' => $integration->id,
                    'identifiable_type' => Base::class,
                    'identifiable_id' => $base->id,
                    'external_type' => 'shopify_variant',
                    'external_id' => $variantSku,
                ]
            );
        }

        return $base;
    }

    /**
     * Parse base option value, handling formats like:
     * - "Lily Pad - Fingering" (descriptor - weight)
     * - "Lily Pad - DK" (descriptor - weight)
     * - "Hopper Sock" (descriptor only, weight inferred)
     * - "Pollywog" (descriptor only, weight inferred)
     *
     * @return array{0: string, 1: Weight}
     */
    private function parseBaseOption(string $optionValue): array
    {
        $parts = explode(' - ', $optionValue);
        $descriptor = trim($parts[0] ?? 'Unknown');
        $weightStr = trim($parts[1] ?? '');

        // Map weight string to enum
        $weightMap = [
            'lace' => Weight::Lace,
            'fingering' => Weight::Fingering,
            'fingering sock' => Weight::Fingering,
            'sock' => Weight::Fingering, // Sock weight maps to Fingering
            'dk' => Weight::DK,
            'worsted' => Weight::Worsted,
            'bulky' => Weight::Bulky,
        ];

        // If no weight specified, try to infer from descriptor
        if (empty($weightStr)) {
            $descriptorLower = strtolower($descriptor);
            if (stripos($descriptorLower, 'sock') !== false) {
                $weightStr = 'sock';
            } else {
                $weightStr = 'fingering'; // Default
            }
        }

        $weight = $weightMap[strtolower($weightStr)] ?? Weight::Fingering;

        return [$descriptor, $weight];
    }

    /**
     * Find base by option value.
     */
    private function findBaseByOptionValue(int $accountId, string $optionValue): ?Base
    {
        [$descriptor, $weight] = $this->parseBaseOption($optionValue);

        // First try exact match
        $base = Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->where('weight', $weight)
            ->first();

        if ($base) {
            return $base;
        }

        // Try matching by normalizing descriptor (remove spaces, case insensitive)
        $normalizedDescriptor = str_replace(' ', '', strtolower($descriptor));
        $bases = Base::where('account_id', $accountId)
            ->where('weight', $weight)
            ->get();

        foreach ($bases as $candidateBase) {
            $normalizedCandidate = str_replace(' ', '', strtolower($candidateBase->descriptor));
            if ($normalizedDescriptor === $normalizedCandidate) {
                return $candidateBase;
            }
        }

        return null;
    }

    /**
     * Find base by descriptor only (for order items).
     */
    private function findBaseByDescriptor(int $accountId, string $descriptor): ?Base
    {
        return Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->first();
    }

    /**
     * Parse line item name to extract colorway and base.
     *
     * @return array{0: string, 1: string}
     */
    private function parseLineItemName(string $lineItemName): array
    {
        // Format: "Colorway - Base" or "Colorway - Base - Weight"
        $parts = explode(' - ', $lineItemName);
        $colorwayName = trim($parts[0] ?? 'Unknown');
        $basePart = trim($parts[1] ?? 'Unknown');

        // Remove weight from base if present
        $baseDescriptor = $basePart;
        if (isset($parts[2])) {
            // Base is just the descriptor part
            $baseDescriptor = $basePart;
        }

        return [$colorwayName, $baseDescriptor];
    }

    /**
     * Map order status from financial and fulfillment status.
     */
    private function mapOrderStatus(string $financialStatus, string $fulfillmentStatus): OrderStatus
    {
        if (strtolower($financialStatus) === 'paid' && strtolower($fulfillmentStatus) === 'fulfilled') {
            return OrderStatus::Closed;
        }

        if (strtolower($financialStatus) === 'paid') {
            return OrderStatus::Open;
        }

        return OrderStatus::Draft;
    }

    /**
     * Build order notes from order row data.
     */
    private function buildOrderNotes(array $row): ?string
    {
        $notes = [];

        if (isset($row['Name'])) {
            $notes[] = "Order: {$row['Name']}";
        }

        if (isset($row['Discount Code'])) {
            $notes[] = "Discount Code: {$row['Discount Code']}";
        }

        if (isset($row['Shipping Method'])) {
            $notes[] = "Shipping: {$row['Shipping Method']}";
        }

        if (isset($row['Payment Method'])) {
            $notes[] = "Payment: {$row['Payment Method']}";
        }

        if (isset($row['Notes'])) {
            $notes[] = $row['Notes'];
        }

        return ! empty($notes) ? implode("\n", $notes) : null;
    }

    /**
     * Clean HTML from description.
     */
    private function cleanHtml(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        // Strip HTML tags and decode entities
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return trim($text) ?: null;
    }

    /**
     * Parse colors from metafield string.
     *
     * @return array<int, Color>|null
     */
    private function parseColors(?string $colorString): ?array
    {
        if (! $colorString) {
            return null;
        }

        // Try to parse as JSON first
        $decoded = json_decode($colorString, true);
        if (is_array($decoded)) {
            $colors = [];
            foreach ($decoded as $colorName) {
                $color = $this->mapColorName($colorName);
                if ($color) {
                    $colors[] = $color;
                }
            }

            return ! empty($colors) ? $colors : null;
        }

        // Try comma-separated
        $colorNames = array_map('trim', explode(',', $colorString));
        $colors = [];
        foreach ($colorNames as $colorName) {
            $color = $this->mapColorName($colorName);
            if ($color) {
                $colors[] = $color;
            }
        }

        return ! empty($colors) ? $colors : null;
    }

    /**
     * Map color name string to Color enum.
     */
    private function mapColorName(string $colorName): ?Color
    {
        $colorMap = [
            'red' => Color::Red,
            'orange' => Color::Orange,
            'yellow' => Color::Yellow,
            'green' => Color::Green,
            'blue' => Color::Blue,
            'purple' => Color::Purple,
            'pink' => Color::Pink,
            'brown' => Color::Brown,
            'black' => Color::Black,
            'white' => Color::White,
            'gray' => Color::Gray,
            'grey' => Color::Gray,
            'teal' => Color::Teal,
            'maroon' => Color::Maroon,
            'navy' => Color::Navy,
            'beige' => Color::Beige,
            'tan' => Color::Tan,
            'coral' => Color::Coral,
            'turquoise' => Color::Turquoise,
        ];

        $normalized = strtolower(trim($colorName));

        return $colorMap[$normalized] ?? null;
    }
}
