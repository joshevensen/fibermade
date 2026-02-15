<?php

namespace App\Services;

/**
 * Maps CSV column names to standardized field names.
 * Supports multiple CSV formats (Shopify native export, third-party exports, etc.)
 */
class CsvColumnMapper
{
    /**
     * Map of possible column names to standardized field names.
     *
     * @var array<string, array<string>>
     */
    private static array $columnMappings = [
        // Product/Colorway fields
        'handle' => ['Handle', 'handle'],
        'title' => ['Title', 'title'],
        'description' => ['Body (HTML)', 'descriptionHtml', 'Description'],
        'status' => ['Status', 'status'],
        'tags' => ['Tags', 'tags'],
        'collections' => ['collections'],
        'lineType' => ['lineType'],

        // Variant/Base fields
        'option1_name' => ['Option1 Name', 'option 1 name'],
        'option1_value' => ['Option1 Value', 'option 1 value'],
        'variant_price' => ['Variant Price', 'price'],
        'variant_sku' => ['Variant SKU', 'sku'],
        'variant_id' => ['Variant ID', 'Variant Id', 'variant_id'],
        'cost' => ['Cost per item', 'cost'],
        'position' => ['position'],

        // Inventory fields
        'available' => ['Available (not editable)', 'location: Studio', 'Variant Inventory Qty', 'variant_inventory_quantity'],

        // Order fields
        'order_id' => ['Id', 'id'],
        'email' => ['Email', 'email'],
        'financial_status' => ['Financial Status', 'financialStatus'],
        'fulfillment_status' => ['Fulfillment Status', 'fulfillmentStatus'],
        'subtotal' => ['Subtotal', 'subtotal'],
        'shipping' => ['Shipping', 'shipping'],
        'discount_amount' => ['Discount Amount', 'discountAmount'],
        'tax_amount' => ['Taxes', 'taxes'],
        'total' => ['Total', 'total'],
        'created_at' => ['Created at', 'createdAt'],
        'lineitem_name' => ['Lineitem name', 'lineitemName'],
        'lineitem_quantity' => ['Lineitem quantity', 'lineitemQuantity'],
        'lineitem_price' => ['Lineitem price', 'lineitemPrice'],

        // Customer fields
        'first_name' => ['First Name', 'firstName'],
        'last_name' => ['Last Name', 'lastName'],
        'phone' => ['Phone', 'phone'],
        'default_address_phone' => ['Default Address Phone', 'defaultAddressPhone'],
        'customer_id' => ['Customer ID', 'customerId'],

        // Collection fields
        'collection_id' => ['id'],
        'collection_title' => ['title'],
        'collection_handle' => ['handle'],
        'collection_description' => ['descriptionHtml'],
        'product_handle' => ['productHandle', 'product handle'],
    ];

    /**
     * Get the value from a row using any possible column name.
     *
     * @param  array<string, string>  $row
     */
    public static function getValue(array $row, string $standardField): ?string
    {
        if (! isset(self::$columnMappings[$standardField])) {
            // Try direct lookup as fallback
            return $row[$standardField] ?? null;
        }

        $possibleColumns = self::$columnMappings[$standardField];

        foreach ($possibleColumns as $columnName) {
            if (isset($row[$columnName])) {
                $value = $row[$columnName];
                if ($value !== '' && $value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Check if a row is a product row (not a variant).
     *
     * @param  array<string, string>  $row
     */
    public static function isProductRow(array $row): bool
    {
        $lineType = self::getValue($row, 'lineType');
        if ($lineType) {
            return strtolower($lineType) === 'product';
        }

        // Fallback: if it has a title and no option1_value, it's likely a product row
        $title = self::getValue($row, 'title');
        $option1Value = self::getValue($row, 'option1_value');

        return ! empty($title) && empty($option1Value);
    }

    /**
     * Check if a row is a variant row.
     *
     * @param  array<string, string>  $row
     */
    public static function isVariantRow(array $row): bool
    {
        $lineType = self::getValue($row, 'lineType');
        if ($lineType) {
            return strtolower($lineType) === 'variant';
        }

        // Fallback: if it has option1_value, it's likely a variant row
        $option1Value = self::getValue($row, 'option1_value');

        return ! empty($option1Value);
    }
}
