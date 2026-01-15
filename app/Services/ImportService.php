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
use App\Models\Colorway;
use App\Models\Customer;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportService
{
    /**
     * Import products and inventory from CSV files.
     *
     * @return array{success: bool, colorways_created: int, colorways_updated: int, bases_created: int, bases_updated: int, inventory_updated: int, errors: array<string>}
     */
    public function importProducts(UploadedFile $productsFile, UploadedFile $inventoryFile, int $accountId): array
    {
        $result = [
            'success' => true,
            'colorways_created' => 0,
            'colorways_updated' => 0,
            'bases_created' => 0,
            'bases_updated' => 0,
            'inventory_updated' => 0,
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

            // Parse inventory CSV
            $inventoryData = $this->parseCsv($inventoryFile);
            if (empty($inventoryData)) {
                throw new \Exception('Inventory CSV file is empty or invalid.');
            }

            // Process products to create/update Colorways and Bases
            $colorwayMap = [];
            $baseMap = [];

            foreach ($productsData as $row) {
                try {
                    // Get or create Colorway
                    $handle = $row['Handle'] ?? null;
                    $title = $row['Title'] ?? null;

                    if (! $handle && ! $title) {
                        continue;
                    }

                    $colorway = $this->getOrCreateColorway($accountId, $handle, $title, $row, $integration);
                    $colorwayMap[$handle] = $colorway;

                    if ($colorway->wasRecentlyCreated) {
                        $result['colorways_created']++;
                    } else {
                        $result['colorways_updated']++;
                    }

                    // Get or create Base from variant
                    $option1Value = $row['Option1 Value'] ?? null;
                    if ($option1Value) {
                        $base = $this->getOrCreateBase($accountId, $option1Value, $row, $integration);
                        $baseMap[$option1Value] = $base;

                        if ($base->wasRecentlyCreated) {
                            $result['bases_created']++;
                        } else {
                            $result['bases_updated']++;
                        }
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing product row: {$e->getMessage()}";
                }
            }

            // Process inventory CSV
            foreach ($inventoryData as $row) {
                try {
                    $handle = $row['Handle'] ?? null;
                    $option1Value = $row['Option1 Value'] ?? null;
                    $available = $row['Available (not editable)'] ?? 0;

                    if (! $handle || ! $option1Value) {
                        continue;
                    }

                    $colorway = $colorwayMap[$handle] ?? Colorway::where('account_id', $accountId)
                        ->whereHas('externalIdentifiers', function ($query) use ($integration, $handle) {
                            $query->where('integration_id', $integration->id)
                                ->where('external_type', 'shopify_product')
                                ->where('external_id', $handle);
                        })
                        ->first();

                    if (! $colorway) {
                        // Try to find by name
                        $title = $row['Title'] ?? null;
                        if ($title) {
                            $colorway = Colorway::where('account_id', $accountId)
                                ->where('name', $title)
                                ->first();
                        }
                    }

                    if (! $colorway) {
                        $result['errors'][] = "Colorway not found for handle: {$handle}";
                        continue;
                    }

                    $base = $baseMap[$option1Value] ?? $this->findBaseByOptionValue($accountId, $option1Value);

                    if (! $base) {
                        $result['errors'][] = "Base not found for option: {$option1Value}";
                        continue;
                    }

                    // Update or create inventory
                    $inventory = Inventory::updateOrCreate(
                        [
                            'account_id' => $accountId,
                            'colorway_id' => $colorway->id,
                            'base_id' => $base->id,
                        ],
                        [
                            'quantity' => (int) $available,
                        ]
                    );

                    if ($inventory->wasRecentlyCreated) {
                        $result['inventory_updated']++;
                    } else {
                        $result['inventory_updated']++;
                    }

                    // Create external identifier for SKU if present
                    $sku = $row['SKU'] ?? null;
                    if ($sku) {
                        ExternalIdentifier::firstOrCreate(
                            [
                                'integration_id' => $integration->id,
                                'identifiable_type' => Inventory::class,
                                'identifiable_id' => $inventory->id,
                                'external_type' => 'shopify_variant_sku',
                                'external_id' => $sku,
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = "Error processing inventory row: {$e->getMessage()}";
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

        // Create if not found
        if (! $colorway) {
            $colorway = Colorway::create([
                'account_id' => $accountId,
                'name' => $title ?: $handle ?: 'Unknown',
                'description' => $this->cleanHtml($row['Body (HTML)'] ?? null),
                'status' => ($row['Published'] ?? 'false') === 'true' ? ColorwayStatus::Active : ColorwayStatus::Retired,
                'colors' => $this->parseColors($row['Color (product.metafields.shopify.color-pattern)'] ?? null),
            ]);
        } else {
            // Update existing
            $colorway->update([
                'description' => $this->cleanHtml($row['Body (HTML)'] ?? null) ?: $colorway->description,
                'status' => ($row['Published'] ?? 'false') === 'true' ? ColorwayStatus::Active : ColorwayStatus::Retired,
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
     * Get or create Base from option value.
     */
    private function getOrCreateBase(int $accountId, string $optionValue, array $row, Integration $integration): Base
    {
        [$descriptor, $weight] = $this->parseBaseOption($optionValue);

        $base = Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->where('weight', $weight)
            ->first();

        if (! $base) {
            $base = Base::create([
                'account_id' => $accountId,
                'descriptor' => $descriptor,
                'weight' => $weight,
                'retail_price' => (float) ($row['Variant Price'] ?? 0),
                'cost' => (float) ($row['Cost per item'] ?? 0),
                'status' => ($row['Status'] ?? 'active') === 'active' ? BaseStatus::Active : BaseStatus::Retired,
            ]);
        } else {
            $base->update([
                'retail_price' => (float) ($row['Variant Price'] ?? $base->retail_price),
                'cost' => (float) ($row['Cost per item'] ?? $base->cost),
                'status' => ($row['Status'] ?? 'active') === 'active' ? BaseStatus::Active : BaseStatus::Retired,
            ]);
        }

        // Create external identifier for variant SKU if present
        $variantSku = $row['Variant SKU'] ?? null;
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
     * Parse base option value (e.g., "Lily Pad - Fingering") into descriptor and weight.
     *
     * @return array{0: string, 1: Weight}
     */
    private function parseBaseOption(string $optionValue): array
    {
        $parts = explode(' - ', $optionValue);
        $descriptor = trim($parts[0] ?? 'Unknown');
        $weightStr = trim($parts[1] ?? 'fingering');

        // Map weight string to enum
        $weightMap = [
            'lace' => Weight::Lace,
            'fingering' => Weight::Fingering,
            'dk' => Weight::DK,
            'worsted' => Weight::Worsted,
            'bulky' => Weight::Bulky,
        ];

        $weight = $weightMap[strtolower($weightStr)] ?? Weight::Fingering;

        return [$descriptor, $weight];
    }

    /**
     * Find base by option value.
     */
    private function findBaseByOptionValue(int $accountId, string $optionValue): ?Base
    {
        [$descriptor, $weight] = $this->parseBaseOption($optionValue);

        return Base::where('account_id', $accountId)
            ->where('descriptor', $descriptor)
            ->where('weight', $weight)
            ->first();
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
